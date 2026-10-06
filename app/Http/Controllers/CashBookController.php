<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\JournalEntryLine;
use App\Models\OpeningBalance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CashBookController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', '現金');
        $year = $request->input('year', now()->format('Y'));
        $month = $request->input('month', now()->format('Y-m'));

        if ($type === '現金') {
            [$start, $end] = $this->monthRange($month);
        } else {
            [$start, $end] = $this->yearRange($year);
        }

        $book = $this->buildBook($type, $start, $end);

        return view('cash_book.index', compact('type', 'year', 'month', 'book'));
    }

    public function pdf(Request $request)
    {
        $type = $request->input('type', '現金');
        $year = $request->input('year', now()->format('Y'));
        $month = $request->input('month', now()->format('Y-m'));

        if ($type === '現金') {
            [$start, $end] = $this->monthRange($month);
            $label = \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y年n月');
            $filename = '現金出納帳_' . $month;
        } else {
            [$start, $end] = $this->yearRange($year);
            $label = $year . '年';
            $filename = '預金出納帳_' . $year;
        }

        $book = $this->buildBook($type, $start, $end);

        $pdf = Pdf::loadView('cash_book.pdf', compact('type', 'book', 'label'))->setPaper('a4', 'portrait');

        return $pdf->stream($filename . '.pdf');
    }

    private function buildBook(string $categoryName, string $start, string $end): array
    {
        $category = ExpenseCategory::where('name', $categoryName)->first();

        if (! $category) {
            return ['category' => null, 'opening' => 0, 'rows' => [], 'closing' => 0];
        }

        $openingBalance = OpeningBalance::where('expense_category_id', $category->id)
            ->where('effective_date', '<=', $start)
            ->orderByDesc('effective_date')
            ->first();

        $baseDate = $openingBalance ? $openingBalance->effective_date->toDateString() : $start;
        $baseAmount = $openingBalance ? $openingBalance->amount : 0;

        $adjustLines = JournalEntryLine::where('expense_category_id', $category->id)
            ->whereHas('entry', fn ($q) => $q->whereBetween('entry_date', [$baseDate, date('Y-m-d', strtotime($start . ' -1 day'))]))
            ->get();

        $opening = $baseAmount + $adjustLines->sum(fn ($l) => $l->side === 'debit' ? $l->amount : -$l->amount);

        $lines = JournalEntryLine::where('expense_category_id', $category->id)
            ->whereHas('entry', fn ($q) => $q->whereDate('entry_date', '>=', $start)->whereDate('entry_date', '<=', $end))
            ->with('entry.lines.category')
            ->get()
            ->sortBy(fn ($l) => $l->entry->entry_date)
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
                'entry_id' => $entry->id,
                'date' => $entry->entry_date,
                'description' => trim(($entry->description ?? '').' '.$opponentName),
                'payee' => $payeeName,
                'income' => $line->side === 'debit' ? $line->amount : null,
                'expense' => $line->side === 'credit' ? $line->amount : null,
                'balance' => $balance,
            ];
        }

        $incomeTotal = array_sum(array_column($rows, 'income'));
        $expenseTotal = array_sum(array_column($rows, 'expense'));
        $receiptTotal = $opening + $incomeTotal;
        $paymentTotal = $balance + $expenseTotal;

        return [
            'income_total' => $incomeTotal,
            'expense_total' => $expenseTotal,
            'receipt_total' => $receiptTotal,
            'payment_total' => $paymentTotal,
            'balanced' => $receiptTotal == $paymentTotal,
            'category' => $category,
            'opening' => $opening,
            'rows' => $rows,
            'closing' => $balance,
        ];
    }

    private function monthRange(string $month): array
    {
        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    private function yearRange(string $year): array
    {
        return [$year . '-01-01', $year . '-12-31'];
    }
}