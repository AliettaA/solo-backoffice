<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\Payee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $entries = JournalEntry::with('lines.category')
            ->whereRaw("DATE_FORMAT(entry_date, '%Y-%m') = ?", [$month])
            ->orderByDesc('entry_date')
            ->get();

        return view('journal_entries.index', compact('entries', 'month'));
    }

    public function create(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->input('month') ?: $request->session()->get('journal_entry_month', now()->format('Y-m'));

        return view('journal_entries.form', [
            'entry' => new JournalEntry(['entry_date' => $month === now()->format('Y-m') ? now()->toDateString() : $month.'-01']),
            'month' => $month,
            'categories' => ExpenseCategory::orderBy('sort_order')->get(),
            'categoriesData' => $this->categoriesData(),
            'creditCategoriesData' => $this->creditCategoriesData(),
            'payeesData' => $this->payeesData(),
            'lines' => [],
            'existingLines' => [],
        ]);
    }

    public function edit(Request $request, JournalEntry $journalEntry)
    {
        $journalEntry->load('lines');

        return view('journal_entries.form', [
            'entry' => $journalEntry,
            'returnTo' => $this->ledgerReturnUrl($request->old('return_to', $request->input('return_to'))),
            'month' => $journalEntry->entry_date->format('Y-m'),
            'categories' => ExpenseCategory::orderBy('sort_order')->get(),
            'categoriesData' => $this->categoriesData(),
            'creditCategoriesData' => $this->creditCategoriesData(),
            'payeesData' => $this->payeesData(),
            'lines' => $journalEntry->lines,
            'existingLines' => $journalEntry->lines->map(function ($line) {
                return [
                    'side' => $line->side,
                    'expense_category_id' => $line->expense_category_id,
                    'counterparty_name' => $line->counterparty_name,
                    'amount' => $line->amount,
                    'memo' => $line->memo,
                ];
            })->values()->all(),
        ]);
    }

    private function payeesData(): array
    {
        return Payee::orderBy('name')->pluck('name')->values()->all();
    }

    private function categoriesData(): array
    {
        return ExpenseCategory::orderBy('sort_order')->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->values()
            ->all();
    }

    private function creditCategoriesData(): array
    {
        $allowedNames = ['現金', '銀行預金', '売上', '源泉所得税預り金', '未払金', '受取利息'];

        return ExpenseCategory::whereIn('name', $allowedNames)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->values()
            ->all();
    }

    public function store(Request $request)
    {
        $data = $this->validateEntry($request);

        DB::transaction(function () use ($data) {
            $entry = JournalEntry::create([
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
            ]);

            $this->saveLines($entry, $data['lines']);
        });

        $request->session()->put('journal_entry_month', $data['target_month']);

        return redirect()->route('journal-entries.index', ['month' => $data['target_month']])
            ->with('status', '伝票を作成しました');
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        $data = $this->validateEntry($request);

        DB::transaction(function () use ($data, $journalEntry) {
            $journalEntry->update([
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
            ]);

            $journalEntry->lines()->delete();
            $this->saveLines($journalEntry, $data['lines']);
        });

        $request->session()->put('journal_entry_month', $data['target_month']);

        if ($returnTo = $this->ledgerReturnUrl($request->input('return_to'))) {
            return redirect()->to($returnTo)->with('status', '伝票を更新しました');
        }

        return redirect()->route('journal-entries.index', ['month' => $data['target_month']])
            ->with('status', '伝票を更新しました');
    }

    private function ledgerReturnUrl(mixed $value): ?string
    {
        if (!is_string($value) || strlen($value) > 2000 || preg_match('/[\\\\\r\n]/', $value)) {
            return null;
        }
        $parts = parse_url($value);
        if (!$parts || isset($parts['host']) || isset($parts['scheme']) || isset($parts['fragment'])
            || !in_array($parts['path'] ?? '', ['/general-ledger', '/cash-book'], true)) {
            return null;
        }
        return $value;
    }

    public function destroy(JournalEntry $journalEntry)
    {
        $month = $journalEntry->entry_date->format('Y-m');
        $journalEntry->delete();

        return redirect()->route('journal-entries.index', ['month' => $month])
            ->with('status', '伝票を削除しました');
    }

    private function saveLines(JournalEntry $entry, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $line) {
            $categoryId = $line['expense_category_id'];

            if ($categoryId === 'other' && ! empty($line['new_category_name'])) {
                $category = ExpenseCategory::firstOrCreate(
                    ['name' => $line['new_category_name']],
                    ['sort_order' => 999]
                );
                $categoryId = $category->id;
            }

            $entry->lines()->create([
                'side' => $line['side'],
                'expense_category_id' => $categoryId,
                'counterparty_name' => $line['counterparty_name'] ?? null,
                'amount' => $line['amount'],
                'memo' => $line['memo'] ?? null,
                'sort_order' => $sort++,
            ]);
        }
    }

    private function validateEntry(Request $request): array
    {
        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.side' => ['required', 'in:debit,credit'],
            'lines.*.expense_category_id' => ['bail', 'required', function ($attribute, $value, $fail) {
                if ($value !== 'other' && (!is_scalar($value) || !ctype_digit((string) $value) || !ExpenseCategory::whereKey($value)->exists())) {
                    $fail('有効な勘定科目を選択してください。');
                }
            }],
            'lines.*.new_category_name' => ['required_if:lines.*.expense_category_id,other', 'nullable', 'string', 'max:50'],
            'lines.*.counterparty_name' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required', 'integer', 'min:1'],
            'lines.*.memo' => ['nullable', 'string'],
        ], [
            'target_month.required' => '対象年月を選択してください。',
            'target_month.date_format' => '対象年月を正しく入力してください。',
            'entry_date.required' => '伝票の日付を入力してください。',
            'entry_date.date_format' => '伝票の日付を正しく入力してください。',
            'lines.required' => '借方と貸方の明細を入力してください。',
            'lines.min' => '借方と貸方の明細を入力してください。',
            'lines.*.expense_category_id.required' => '勘定科目を選択してください。',
            'lines.*.new_category_name.required_if' => '「その他」を選んだ行には新しい勘定科目名を入力してください。',
            'lines.*.amount.required' => '明細の金額を入力してください。',
            'lines.*.amount.integer' => '明細の金額は整数で入力してください。',
            'lines.*.amount.min' => '明細の金額は1円以上で入力してください。',
        ]);

        if (substr($data['entry_date'], 0, 7) !== $data['target_month']) {
            throw ValidationException::withMessages(['entry_date' => '日付は対象年月（'.$data['target_month'].'）の範囲内で指定してください。']);
        }

        // 貸借バランスチェック(借方合計 = 貸方合計)
        $debitTotal = collect($data['lines'])->where('side', 'debit')->sum('amount');
        $creditTotal = collect($data['lines'])->where('side', 'credit')->sum('amount');

        if ($debitTotal !== $creditTotal) {
            throw ValidationException::withMessages([
                'lines' => '借方合計(¥'.number_format($debitTotal).')と貸方合計(¥'.number_format($creditTotal).')が一致していません。',
            ]);
        }

        return $data;
    }

    public function pdf(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $entries = JournalEntry::with('lines.category')
            ->whereRaw("DATE_FORMAT(entry_date, '%Y-%m') = ?", [$month])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $debitTotal = 0;
        $creditTotal = 0;
        foreach ($entries as $entry) {
            $debitTotal += $entry->lines->where('side', 'debit')->sum('amount');
            $creditTotal += $entry->lines->where('side', 'credit')->sum('amount');
        }

        $pdf = Pdf::loadView('journal_entries.pdf', [
            'entries' => $entries,
            'month' => $month,
            'debitTotal' => $debitTotal,
            'creditTotal' => $creditTotal,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('仕訳帳_'.$month.'.pdf');
    }
}
