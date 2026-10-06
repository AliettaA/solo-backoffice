<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Services\FinancialStatements;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialStatementController extends Controller
{
    public function index(Request $request, FinancialStatements $statements)
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:1900,2100']]);

        return view('financial_statements.index', $statements->build((int) ($data['year'] ?? now()->year)));
    }

    public function pdf(Request $request, FinancialStatements $statements)
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:1900,2100']]);
        $year = (int) ($data['year'] ?? now()->year);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('financial_statements.pdf', $statements->build($year))
            ->setPaper('a4', 'portrait')->stream('決算書_'.$year.'.pdf');
    }

    public function classify(Request $request, ExpenseCategory $category)
    {
        $data = $request->validate(['statement_type' => ['nullable', Rule::in(array_keys(FinancialStatements::TYPES))]]);
        $category->update($data);

        return back()->with('status', '科目分類を保存しました');
    }
}
