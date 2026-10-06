<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\OpeningBalance;
use App\Services\FinancialStatements;
use Illuminate\Http\Request;

class OpeningBalanceController extends Controller
{
    public function index()
    {
        $balances = OpeningBalance::with('category')->orderBy('effective_date')->get();
        $categories = ExpenseCategory::orderBy('sort_order')->get()->filter(fn ($category) => in_array($category->statement_type ?: FinancialStatements::defaultType($category->name), ['asset', 'liability', 'capital'], true));

        return view('opening_balances.index', compact('balances', 'categories'));
    }

    public function store(Request $request)
    {
        $allowedIds = ExpenseCategory::all()->filter(fn ($category) => in_array($category->statement_type ?: FinancialStatements::defaultType($category->name), ['asset', 'liability', 'capital'], true))->pluck('id');

        $data = $request->validate([
            'expense_category_id' => ['required', 'in:'.$allowedIds->implode(',')],
            'effective_date' => ['required', 'date'],
            'amount' => ['required', 'integer'],
            'memo' => ['nullable', 'string'],
            'balance_side' => ['nullable', 'in:debit,credit'],
        ]);

        if (isset($data['balance_side'])) {
            $data['amount'] = abs($data['amount']) * ($data['balance_side'] === 'credit' ? -1 : 1);
        }

        OpeningBalance::updateOrCreate(
            ['expense_category_id' => $data['expense_category_id'], 'effective_date' => $data['effective_date']],
            ['amount' => $data['amount'], 'memo' => $data['memo'] ?? null]
        );

        return back()->with('status', '期首残高を登録しました');
    }

    public function destroy(OpeningBalance $openingBalance)
    {
        $openingBalance->delete();

        return back()->with('status', '期首残高を削除しました');
    }
}
