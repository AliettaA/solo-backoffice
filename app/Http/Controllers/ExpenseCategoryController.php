<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use App\Services\FinancialStatements;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    private const SYSTEM_NAMES = ['現金', '銀行預金', '売上', '給料賃金', '労務費', '給料', '源泉所得税預り金', '市県民税預り金', '預り金', '未払金', '受取利息'];

    private function locked(ExpenseCategory $category): bool
    {
        return in_array($category->name, self::SYSTEM_NAMES, true)
            || JournalEntryLine::where('expense_category_id', $category->id)->exists()
            || OpeningBalance::where('expense_category_id', $category->id)->exists();
    }

    public function index()
    {
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('id')->get();
        $lockedIds = $categories->filter(fn ($category) => $this->locked($category))->pluck('id')->all();
        return view('expense_categories.index', compact('categories', 'lockedIds'));
    }

    private function validateCategory(Request $request, ?ExpenseCategory $category = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('expense_categories', 'name')->ignore($category?->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'statement_type' => ['nullable', Rule::in(array_keys(FinancialStatements::TYPES))],
        ]);
        $data['sort_order'] = $data['sort_order'] ?? $category?->sort_order ?? 100;
        $data['statement_type'] = $data['statement_type'] ?? $category?->statement_type
            ?? FinancialStatements::defaultType($category?->name ?? $data['name']);
        return $data;
    }

    public function store(Request $request)
    {
        ExpenseCategory::create($this->validateCategory($request));
        return back()->with('status', '勘定科目を追加しました');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $this->validateCategory($request, $expenseCategory);
        if ($data['name'] !== $expenseCategory->name && $this->locked($expenseCategory)) {
            throw ValidationException::withMessages(['name' => '使用中または自動処理で使う科目の名称は変更できません。分類と表示順は変更できます。']);
        }
        $expenseCategory->update($data);
        return back()->with('status', '勘定科目を更新しました');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if ($this->locked($expenseCategory)) {
            throw ValidationException::withMessages(['category' => '使用中または自動処理で使う科目は削除できません。']);
        }
        $expenseCategory->delete();
        return back()->with('status', '勘定科目を削除しました');
    }
}
