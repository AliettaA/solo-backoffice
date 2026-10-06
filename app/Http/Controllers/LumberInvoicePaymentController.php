<?php

namespace App\Http\Controllers;

use App\Models\LumberInvoice;
use App\Models\LumberInvoicePayment;
use Illuminate\Http\Request;

class LumberInvoicePaymentController extends Controller
{
    public function store(Request $request, LumberInvoice $lumberInvoice)
    {
        $data = $request->validate([
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'string', 'max:50'],
            'memo' => ['nullable', 'string'],
        ]);

        $lumberInvoice->payments()->create($data);
        $lumberInvoice->recalculatePaidAmount();

        return back()->with('status', '入金を記録しました');
    }

    public function destroy(LumberInvoicePayment $payment)
    {
        $invoice = $payment->invoice;
        $payment->delete();
        $invoice->recalculatePaidAmount();

        return back()->with('status', '入金記録を削除しました');
    }
}