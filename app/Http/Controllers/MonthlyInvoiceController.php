<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\LumberInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'client_id' => ['nullable', 'integer', 'exists:clients,id']]);
        $month = $request->input('month') ?: $this->currentBillingMonth();
        $clientId = $request->input('client_id', null);

        $clients = Client::orderBy('name')->get();
        [$start, $end] = $this->billingPeriod($month);

        $invoices = collect();
        $summary = null;

        if ($clientId) {
            $invoices = LumberInvoice::where('client_id', $clientId)
                ->whereBetween('issue_date', [$start, $end])
                ->orderBy('issue_date')
                ->with(['lines', 'productLines'])
                ->get();

            $summary = $this->buildSummary($invoices);
        }

        return view('monthly_invoices.index', compact('clients', 'month', 'clientId', 'start', 'end', 'invoices', 'summary'));
    }

    public function pdf(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'client_id' => ['required', 'integer', 'exists:clients,id']]);
        $month = $request->input('month') ?: $this->currentBillingMonth();
        $clientId = $request->input('client_id');

        $client = Client::findOrFail($clientId);
        [$start, $end] = $this->billingPeriod($month);

        $invoices = LumberInvoice::where('client_id', $clientId)
            ->whereBetween('issue_date', [$start, $end])
            ->orderBy('issue_date')
            ->with(['lines', 'productLines'])
            ->get();

        $summary = $this->buildSummary($invoices);
        $company = Company::first();

        $pdf = Pdf::loadView('monthly_invoices.pdf', [
            'client' => $client,
            'invoices' => $invoices,
            'summary' => $summary,
            'company' => $company,
            'start' => $start,
            'end' => $end,
            'month' => $month,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('月次請求書_' . $client->name . '_' . $month . '.pdf');
    }

    private function buildSummary($invoices): array
    {
        $subtotal10 = $invoices->sum('subtotal');   // 税抜金額の合計(丸め前なのでそのまま合計してOK)
        $subtotal8 = $invoices->sum('subtotal_8');  // 同上

        $tax10 = (int) round($subtotal10 * 0.10);   // 消費税は月合計の小計から1回だけ計算
        $tax8 = (int) round($subtotal8 * 0.08);

        $total = $subtotal10 + $tax10 + $subtotal8 + $tax8;

        return [
            'subtotal_10' => $subtotal10,
            'tax_10' => $tax10,
            'subtotal_8' => $subtotal8,
            'tax_8' => $tax8,
            'total' => $total,
            'invoice_count' => $invoices->count(),
        ];
    }

    private function billingPeriod(string $month): array
    {
        $anchor = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        $end = $anchor->copy()->day(20);
        $start = $end->copy()->subMonthNoOverflow()->addDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    private function currentBillingMonth(): string
    {
        $today = now();

        if ($today->day >= 21) {
            return $today->copy()->addMonthNoOverflow()->format('Y-m');
        }

        return $today->format('Y-m');
    }
}