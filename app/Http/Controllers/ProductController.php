<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->paginate(20);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.form', ['product' => new Product()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Product::create($data);
        return redirect()->route('products.index')->with('status', '品目を登録しました');
    }

    public function edit(Product $product)
    {
        return view('products.form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request, $product);
        $product->update($data);
        return redirect()->route('products.index')->with('status', '品目を更新しました');
    }

    public function destroy(Product $product)
    {
        if (\Illuminate\Support\Facades\DB::table('lumber_invoice_product_lines')->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['name' => '取引で使用中のため削除できません。']);
        }
        $product->delete();
        return redirect()->route('products.index')->with('status', '品目を削除しました');
    }

    private function validateData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'unit' => ['nullable', 'string', 'max:20'],
            'tax_rate' => ['required', 'in:10,8'],
        ]);

        $data['unit'] = ($data['unit'] ?? null) ?: '式';
        $data['is_reduced_tax'] = $data['tax_rate'] == 8;

        return $data;
    }
}