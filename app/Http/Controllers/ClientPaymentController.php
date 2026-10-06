<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\LumberInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientPaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'client_id' => ['nullable', 'integer', 'exists:clients,id']]);
        $month = $request->input('month') ?: $this->currentBillingMonth();

        [$start, $end] = $this->billingPeriod($month);

        $clients = Client::orderBy('name')->get();

        $summaries = $clients->map(function ($client) use ($month, $start, $end) {
            $invoiceTotal = LumberInvoice::where('client_id', $client->id)
                ->whereBetween('issue_date', [$start, $end])
                ->sum('total');

            $paymentTotal = ClientPayment::where('client_id', $client->id)
                ->where('target_month', $month)
                ->sum('amount');

            return [
                'client' => $client,
                'invoice_total' => $invoiceTotal,
                'payment_total' => $paymentTotal,
                'remaining' => $invoiceTotal - $paymentTotal,
                'payments' => ClientPayment::where('client_id', $client->id)
                    ->where('target_month', $month)
                    ->orderBy('paid_at')
                    ->get(),
            ];
        })->filter(fn ($s) => $s['invoice_total'] > 0 || $s['payments']->count() > 0)
        ->values();

        return view('payments.index', compact('summaries', 'month', 'clients', 'start', 'end'));
    }

/**
 * 締め日(21日〜翌月20日)の期間を計算
 * $month は "2026-09" のような対象月文字列。
 * "2026-09" は 2026/8/21 〜 2026/9/20 を意味する。
 */
    private function billingPeriod(string $month): array
    {
        $anchor = \Carbon\Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

        $end = $anchor->copy()->day(20); // 対象月の20日
        $start = $end->copy()->subMonthNoOverflow()->addDay(); // 前月21日

        return [$start->toDateString(), $end->toDateString()];
    }

    private function currentBillingMonth(): string
    {
        $today = now();

        // 21日以降は「翌月」扱いの締め期間に入る
        if ($today->day >= 21) {
            return $today->copy()->addMonthNoOverflow()->format('Y-m');
        }

        return $today->format('Y-m');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'target_month' => ['required', 'date_format:Y-m'],
            'method' => ['required', 'in:銀行振込,現金,その他'],
            'memo' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            Client::whereKey($data['client_id'])->lockForUpdate()->firstOrFail();
            $duplicates = ClientPayment::where('client_id', $data['client_id'])->whereDate('paid_at', $data['paid_at'])
                ->where('amount', $data['amount'])->where('method', $data['method'])->pluck('id')->all();
            $fingerprint = hash('sha256', json_encode([$data, $duplicates]));
            if ($duplicates && (!$request->boolean('confirm_duplicate') || $request->session()->get('payment_duplicate') !== $fingerprint)) {
                $request->session()->put('payment_duplicate', $fingerprint);
                throw \Illuminate\Validation\ValidationException::withMessages(['duplicate' => '同じ取引先・入金日・金額・入金方法の記録があります。別の入金であることを確認して、確認欄にチェックを入れて再登録してください。']);
            }
            $request->session()->forget('payment_duplicate');
            $payment = ClientPayment::create($data);
            $payment->syncJournalEntry();
        });

        return redirect()->route('payments.index', ['month' => $data['target_month']])
            ->with('status', $data['method'] === 'その他' ? '入金を記録しました（その他は自動仕訳の対象外です）' : '入金を記録し、振替伝票を作成しました');
    }

    public function destroy(ClientPayment $payment)
    {
        $month = $payment->target_month;
        DB::transaction(function () use ($payment) {
            $payment->deleteJournalEntry();
            $payment->delete();
        });

        return redirect()->route('payments.index', ['month' => $month])
            ->with('status', '入金記録を削除しました');
    }
}