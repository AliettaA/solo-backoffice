<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\JournalEntryLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class GeneralLedgerController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', now()->format('Y'));
        $categoryId = $request->input('category_id');

        $categories = ExpenseCategory::orderBy('sort_order')->get();

        $ledger = $categoryId ? $this->displayLedger((int) $categoryId, $year) : null;

        return view('general_ledger.index', compact('categories', 'year', 'categoryId', 'ledger'));
    }

    public function pdf(Request $request)
    {
        $year = $request->input('year', now()->format('Y'));
        [$start, $end] = $this->yearRange($year);

        $categoryIds = JournalEntryLine::whereHas('entry', function ($q) use ($start, $end) {
            $q->whereDate('entry_date', '>=', $start)->whereDate('entry_date', '<=', $end);
        })->distinct()->pluck('expense_category_id');

        $categories = ExpenseCategory::whereIn('id', $categoryIds)->whereNotIn('name', ['現金', '銀行預金'])->orderBy('sort_order')->get();

        $ledgers = $categories->unique(fn ($c) => in_array($c->name, ['預り金', '市県民税預り金'], true) ? 'deposits' : $c->id)->map(fn ($c) => $this->displayLedger($c->id, $year));

        $pdf = Pdf::loadView('general_ledger.pdf', compact('ledgers', 'year'))->setPaper('a4', 'portrait');

        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $text = $pageNumber.'/'.$pageCount;
            $font = $fontMetrics->getFont('Helvetica');
            $size = 10;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $canvas->text(($canvas->get_width() - $width) / 2, $canvas->get_height() - 26, $text, $font, $size);
        });

        return $pdf->stream('総勘定元帳_' . $year . '.pdf');
    }

    private function displayLedger(int $categoryId, string $year): array
    {
        $ledger = $this->buildLedger($categoryId, $year);
        if (in_array($ledger['category']->name, ['預り金', '市県民税預り金'], true)) {
            $ledger['deposit_ledgers'] = ExpenseCategory::whereIn('name', ['預り金', '市県民税預り金'])->orderBy('sort_order')->get()
                ->map(fn ($category) => $this->buildLedger($category->id, $year));
        }
        return $ledger;
    }

    private function buildLedger(int $categoryId, string $year): array
    {
        $category = ExpenseCategory::findOrFail($categoryId);
        [$start, $end] = $this->yearRange($year);

        $openingBalance = \App\Models\OpeningBalance::where('expense_category_id', $categoryId)
            ->where('effective_date', '<=', $start)
            ->orderByDesc('effective_date')
            ->first();

        $baseDate = $openingBalance ? $openingBalance->effective_date->toDateString() : $start;
        $baseAmount = $openingBalance ? $openingBalance->amount : 0;

        $adjustLines = JournalEntryLine::where('expense_category_id', $categoryId)
            ->whereHas('entry', fn($q) => $q->whereBetween('entry_date', [$baseDate, date('Y-m-d', strtotime($start . ' -1 day'))]))
            ->get();

        $opening = $baseAmount + $adjustLines->sum(fn($l) => $l->side === 'debit' ? $l->amount : -$l->amount);

        $lines = JournalEntryLine::where('expense_category_id', $categoryId)
            ->whereHas('entry', fn($q) => $q->whereDate('entry_date', '>=', $start)->whereDate('entry_date', '<=', $end))
            ->with('entry.lines.category')
            ->get()
            ->sortBy(fn($l) => $l->entry->entry_date)
            ->values();

        $balance = $opening;
        $rows = [];

        foreach ($lines as $line) {
            $entry = $line->entry;
            $opponentLines = $entry->lines->where('side', $line->side === 'debit' ? 'credit' : 'debit');
            $opponentName = $opponentLines->count() > 1
                ? '諸口'
                : ($opponentLines->first()->category->name ?? '');

            $payeeName = $entry->lines
                ->where('side', 'debit')
                ->pluck('counterparty_name')
                ->filter()
                ->unique()
                ->implode(' / ');

            $balance += $line->side === 'debit' ? $line->amount : -$line->amount;

            $rows[] = [
                'withholding_group' => $line->side === 'debit' ? 'settlement' : ($opponentLines->pluck('category.name')->unique()->count() === 1 && in_array($opponentLines->first()?->category?->name, ['労務費', '雑費'], true) ? $opponentLines->first()->category->name : 'other'),
                'side' => $line->side,
                'entry_id' => $entry->id,
                'date' => $entry->entry_date,
                'item' => $opponentName,
                'payee' => $payeeName,
                'amount' => $line->amount,
                'balance' => $balance,
            ];
        }

        return [
            'category' => $category,
            'opening' => $opening,
            'rows' => $rows,
            'closing' => $balance,
            'withholding_sections' => $category->name === '源泉所得税預り金' ? collect(['労務費' => '労務費分（預り・貸方）', '雑費' => '雑費分（預り・貸方）', 'settlement' => '納付・精算分（借方）', 'other' => 'その他・分類確認（貸方）'])->map(function ($title, $group) use ($rows) {
                $sectionRows = collect($rows)->where('withholding_group', $group)->values();
                return ['title' => $title, 'rows' => $sectionRows, 'total' => $sectionRows->sum('amount'), 'group' => $group];
            })->filter(fn ($s) => $s['group'] !== 'other' || $s['rows']->isNotEmpty())->values()->all() : null,
            'payable_sections' => $category->name === '未払金' ? collect(['debit' => '支払い分（借方）', 'credit' => '未払計上分（貸方）'])->map(function ($title, $side) use ($rows) {
                $sectionRows = collect($rows)->where('side', $side)->values();
                return ['title' => $title, 'rows' => $sectionRows, 'total' => $sectionRows->sum('amount')];
            })->values()->all() : null,
        ];
    }
    private function yearRange(string $year): array
    {
        return [$year.'-01-01', $year.'-12-31'];
    }
}