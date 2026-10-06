<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\LumberInvoice;
use App\Models\LumberSpecies;
use App\Models\LumberWidthPrice;
use App\Models\PricingSetting;
use App\Models\Product;
use App\Models\QuantityTierSurcharge;
use App\Models\ThicknessSurcharge;
use App\Services\LumberPriceCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LumberInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->input('month');

        $query = LumberInvoice::with(['client', 'lines'])->orderByDesc('issue_date');

        if ($month) {
            $query->whereRaw("DATE_FORMAT(issue_date, '%Y-%m') = ?", [$month]);
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('lumber_invoices.index', compact('invoices', 'month'));
    }

    public function create()
    {
        return view('lumber_invoices.form', array_merge(
            $this->formData(),
            [
                'invoice' => new LumberInvoice([
                    'issue_date' => now()->toDateString(),
                    'client_id' => null,
                ]),
                'lines' => [],
                'existingLines' => [],
                'existingProductLines' => [],
            ]
        ));
    }

    public function store(Request $request, LumberPriceCalculator $calculator)
    {
        $data = $this->validateInvoice($request);

        DB::transaction(function () use ($data, $calculator) {
            $invoice = LumberInvoice::create([
                'invoice_number' => $this->generateInvoiceNumber($data['issue_date']),
                'client_id' => $data['client_id'],
                'issue_date' => $data['issue_date'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'invoice_adjustment_amount' => $data['invoice_adjustment_amount'] ?? 0,
                'invoice_adjustment_description' => $data['invoice_adjustment_description'] ?? null,
            ]);

            $this->saveLines($invoice, $data['lines'] ?? [], $calculator);
            $this->saveProductLines($invoice, $data['product_lines'] ?? []);

            $invoice->load(['lines', 'productLines']);
            $invoice->recalculateTotals();
        });

        return redirect()->route('lumber-invoices.index')->with('status', '請求書を作成しました');
    }

    public function edit(LumberInvoice $lumberInvoice)
    {
        $lumberInvoice->load(['lines.tiers', 'productLines']);

        return view('lumber_invoices.form', array_merge(
            $this->formData(),
            [
                'invoice' => $lumberInvoice,
                'lines' => $lumberInvoice->lines,
                'existingLines' => $this->buildExistingLines($lumberInvoice->lines),
                'existingProductLines' => $this->buildExistingProductLines($lumberInvoice->productLines),
            ]
        ));
    }

    public function update(Request $request, LumberInvoice $lumberInvoice, LumberPriceCalculator $calculator)
    {
        $data = $this->validateInvoice($request);

        DB::transaction(function () use ($data, $lumberInvoice, $calculator) {
            $lumberInvoice->update([
                'client_id' => $data['client_id'],
                'issue_date' => $data['issue_date'],
                'notes' => $data['notes'] ?? null,
                'invoice_adjustment_amount' => $data['invoice_adjustment_amount'] ?? 0,
                'invoice_adjustment_description' => $data['invoice_adjustment_description'] ?? null,
            ]);

            // 既存の明細を全削除して作り直す(シンプルさ優先)
            foreach ($lumberInvoice->lines as $line) {
                $line->tiers()->delete();
                $line->delete();
            }
            $lumberInvoice->productLines()->delete();

            $this->saveLines($lumberInvoice, $data['lines'] ?? [], $calculator);
            $this->saveProductLines($lumberInvoice, $data['product_lines'] ?? []);

            $lumberInvoice->load(['lines', 'productLines']);
            $lumberInvoice->recalculateTotals();
        });

        return redirect()->route('lumber-invoices.index')->with('status', '請求書を更新しました');
    }

    public function destroy(LumberInvoice $lumberInvoice)
    {
        $lumberInvoice->delete(); // cascade で lines / tiers / productLines も削除

        return redirect()->route('lumber-invoices.index')->with('status', '請求書を削除しました');
    }

    public function pdf(LumberInvoice $lumberInvoice)
    {
        $lumberInvoice->load(['client', 'lines.species', 'lines.tiers', 'productLines']);

        $company = Company::first();

        $pdf = Pdf::loadView('lumber_invoices.pdf', [
            'invoice' => $lumberInvoice,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($lumberInvoice->invoice_number.'.pdf');
    }

    private function saveLines(LumberInvoice $invoice, array $lines, LumberPriceCalculator $calculator): void
    {
        $sort = 0;
        foreach ($lines as $lineData) {
            $hasQty = collect($lineData['quantities'] ?? [])->sum() > 0;
            $adjustment = (int) ($lineData['adjustment_amount'] ?? 0);
            if (! $hasQty && $adjustment === 0 && (int) ($lineData['returned_quantity'] ?? 0) === 0) {
                continue;
            }

            $species = LumberSpecies::findOrFail($lineData['lumber_species_id']);

            $line = $invoice->lines()->create([
                'lumber_species_id' => $species->id,
                'thickness' => $lineData['thickness'],
                'width' => $lineData['width'],
                'length' => $lineData['length'],
                'adjustment_amount' => $adjustment,
                'returned_quantity' => $lineData['returned_quantity'] ?? 0,
                'unit_price_adjustment' => $lineData['unit_price_adjustment'] ?? 0,
                'sort_order' => $sort++,
            ]);

            foreach ($lineData['quantities'] ?? [] as $tierId => $qty) {
                $qty = (int) $qty;
                if ($qty <= 0) {
                    continue;
                }

                $tier = QuantityTierSurcharge::findOrFail($tierId);
                $unitPrice = $calculator->calculateUnitPrice(
                    $species,
                    (float) $lineData['thickness'],
                    (float) $lineData['width'],
                    (float) $lineData['length'],
                    $tier
                );

                $unitPrice += (int) ($lineData['unit_price_adjustment'] ?? 0);
                if ($unitPrice < 0) {
                    throw ValidationException::withMessages(['lines' => '調整後の単価がマイナスになります。単価調整額を確認してください。']);
                }

                if ($unitPrice > 4294967295 || $unitPrice * $qty > 4294967295) {
                    throw ValidationException::withMessages(['lines' => '単価または明細金額が保存可能な上限を超えています。数量・単価を確認してください。']);
                }
                $line->tiers()->create([
                    'tier_label' => $tier->label,
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'amount' => $unitPrice * $qty,
                ]);
            }

            $line->load('tiers');
            $line->recalculate();
        }
    }

    private function saveProductLines(LumberInvoice $invoice, array $productLines): void
    {
        $sort = 0;
        foreach ($productLines as $line) {
            if (empty($line['description']) || (float) ($line['quantity'] ?? 0) <= 0) {
                continue;
            }

            $quantity = (float) $line['quantity'];
            $unitPrice = (int) $line['unit_price'];

            if (round($quantity * $unitPrice) > 4294967295) {
                throw ValidationException::withMessages(['product_lines' => '品目明細の金額が保存可能な上限を超えています。']);
            }
            $invoice->productLines()->create([
                'product_id' => $line['product_id'] ?? null,
                'description' => $line['description'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $line['tax_rate'] ?? 10,
                'amount' => (int) round($quantity * $unitPrice),
                'sort_order' => $sort++,
            ]);
        }
    }

    private function buildExistingLines($lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $quantities = [];
            foreach ($line->tiers as $tier) {
                $quantities[$tier->tier_label] = $tier->quantity;
            }

            $result[] = [
                'lumber_species_id' => $line->lumber_species_id,
                'thickness' => $line->thickness,
                'width' => $line->width,
                'length' => $line->length,
                'adjustment_amount' => $line->adjustment_amount,
                'unit_price_adjustment' => $line->unit_price_adjustment,
                'returned_quantity' => $line->returned_quantity,
                'quantities' => $quantities,
            ];
        }

        return $result;
    }

    private function buildExistingProductLines($productLines): array
    {
        return $productLines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_rate' => $line->tax_rate,
            ];
        })->values()->all();
    }

    private function formData(): array
    {
        $species = LumberSpecies::orderBy('id')->get();
        $tiers = QuantityTierSurcharge::orderBy('sort_order')->get();

        $speciesData = [];
        foreach ($species as $s) {
            $speciesData[] = [
                'id' => $s->id,
                'name' => $s->name,
                'pricing_type' => $s->pricing_type,
                'flat_price' => $s->flat_price,
                'apply_thickness_surcharge' => $s->apply_thickness_surcharge,
                'apply_length_adjustment' => $s->apply_length_adjustment,
            ];
        }

        $tiersData = [];
        foreach ($tiers as $t) {
            $tiersData[] = [
                'id' => $t->id,
                'label' => $t->label,
                'surcharge_amount' => $t->surcharge_amount,
            ];
        }

        $products = Product::orderBy('name')->get();
        $productsData = [];
        foreach ($products as $p) {
            $productsData[] = [
                'id' => $p->id,
                'name' => $p->name,
                'unit_price' => $p->unit_price,
                'tax_rate' => $p->tax_rate,
            ];
        }

        $widthPrices = LumberWidthPrice::all(['lumber_species_id', 'width', 'base_price'])->toArray();
        $thicknessSurcharges = ThicknessSurcharge::orderBy('min_thickness')
            ->get(['min_thickness', 'surcharge_amount'])->toArray();
        $pricingSettings = PricingSetting::pluck('value', 'key')->toArray();

        return [
            'clients' => Client::orderBy('name')->get(),
            'species' => $species,
            'tiers' => $tiers,
            'speciesData' => $speciesData,
            'tiersData' => $tiersData,
            'widthPrices' => $widthPrices,
            'thicknessSurcharges' => $thicknessSurcharges,
            'pricingSettings' => $pricingSettings,
            'products' => $products,
            'productsData' => $productsData,
        ];
    }

    private function generateInvoiceNumber(string $issueDate): string
    {
        $prefix = 'INV-'.str_replace('-', '', $issueDate);
        $count = LumberInvoice::where('invoice_number', 'like', $prefix.'%')->count();

        return $prefix.'-'.str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }

    private function validateInvoice(Request $request): array
    {
        $request->validate([
            'lines' => ['nullable', 'array', 'max:200'], 'lines.*' => ['array'],
            'lines.*.quantities' => ['nullable', 'array'],
            'lines.*.quantities.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'lines.*.adjustment_amount' => ['nullable', 'integer', 'between:-2147483648,2147483647'],
            'lines.*.unit_price_adjustment' => ['nullable', 'integer', 'multiple_of:5', 'between:-2147483648,2147483647'],
            'lines.*.returned_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'product_lines' => ['nullable', 'array', 'max:200'], 'product_lines.*' => ['array'],
        ]);
        // 空行だけを除外する。不正な数量を空行として捨てない。
        $lines = collect($request->input('lines') ?? [])->filter(fn ($line) =>
            collect($line['quantities'] ?? [])->sum() > 0
            || ($line['adjustment_amount'] ?? 0) != 0
            || ($line['unit_price_adjustment'] ?? 0) != 0
            || ($line['returned_quantity'] ?? 0) != 0
        )->values()->all();
        $products = collect($request->input('product_lines') ?? [])->filter(fn ($line) =>
            filled($line['description'] ?? null) || filled($line['product_id'] ?? null)
            || filled($line['unit_price'] ?? null)
            || !in_array($line['quantity'] ?? null, [null, '', 1, '1'], true)
        )->values()->all();
        $request->merge(['lines' => $lines, 'product_lines' => $products]);

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'issue_date' => ['required', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string'],
            'invoice_adjustment_amount' => ['nullable', 'integer'],
            'invoice_adjustment_description' => [Rule::requiredIf((int) $request->input('invoice_adjustment_amount', 0) !== 0), 'nullable', 'string', 'max:100', function ($attribute, $value, $fail) use ($request) {
                if ((int) $request->input('invoice_adjustment_amount', 0) !== 0 && trim((string) $value) === '') {
                    $fail('金額調整の内容を入力してください。');
                }
            }],
            'lines' => ['nullable', 'array'],
            'lines.*.lumber_species_id' => ['required', 'exists:lumber_species,id'],
            'lines.*.thickness' => ['required', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2'],
            'lines.*.width' => ['required', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2'],
            'lines.*.length' => ['required', 'numeric', 'gt:0', 'max:9999.99', 'decimal:0,2'],
            'lines.*.adjustment_amount' => ['nullable', 'integer'],
            'lines.*.unit_price_adjustment' => ['nullable', 'integer', 'multiple_of:5'],
            'lines.*.returned_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'lines.*.quantities' => ['nullable', 'array'],
            'lines.*.quantities.*' => ['nullable', 'integer', 'min:0'],
            'product_lines' => ['nullable', 'array'],
            'product_lines.*.product_id' => ['nullable', 'exists:products,id'],
            'product_lines.*.description' => ['required', 'string', 'max:255'],
            'product_lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000', 'decimal:0,2'],
            'product_lines.*.unit_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'product_lines.*.tax_rate' => ['nullable', 'in:10,8'],
        ]);
        if (!$lines && !$products && (int) ($data['invoice_adjustment_amount'] ?? 0) === 0) {
            throw ValidationException::withMessages(['lines' => '数量・戻し枚数・金額調整または品目明細を1件以上入力してください。']);
        }
        $tierIds = QuantityTierSurcharge::pluck('id')->map(fn ($id) => (string) $id)->all();
        foreach ($lines as $index => $line) {
            foreach ($line['quantities'] ?? [] as $id => $quantity) {
                if (!in_array((string) $id, $tierIds, true)) {
                    throw ValidationException::withMessages(['lines.'.$index.'.quantities' => '数量帯が変更されています。画面を開き直して選択してください。']);
                }
            }
        }
        return $data;
    }
}
