<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LumberInvoice extends Model
{
    protected $fillable = [
        'invoice_number', 'client_id', 'issue_date', 'status',
        'invoice_adjustment_amount', 'invoice_adjustment_description', 'subtotal_8', 'tax_8', 'notes', 'subtotal', 'tax_amount', 'total',
    ];

    protected $casts = ['issue_date' => 'date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lines()
    {
        return $this->hasMany(LumberInvoiceLine::class)->orderBy('sort_order');
    }

    public function recalculateTotals(): void
    {
        $lumberSubtotal = $this->lines->sum('line_total');

        $productSubtotal10 = $this->productLines->where('tax_rate', 10)->sum('amount');
        $productSubtotal8 = $this->productLines->where('tax_rate', 8)->sum('amount');

        $subtotal10 = $lumberSubtotal + $productSubtotal10 + (int) $this->invoice_adjustment_amount; // 木材は常に10%想定
        if ($subtotal10 < 0) {
            throw ValidationException::withMessages(['invoice_adjustment_amount' => '調整後の税抜小計がマイナスになります。金額を確認してください。']);
        }
        $tax10 = (int) round($subtotal10 * 0.10);

        $subtotal8 = $productSubtotal8;
        $tax8 = (int) round($subtotal8 * 0.08);

        $this->update([
            'subtotal' => $subtotal10,
            'tax_amount' => $tax10,
            'subtotal_8' => $subtotal8,
            'tax_8' => $tax8,
            'total' => $subtotal10 + $tax10 + $subtotal8 + $tax8,
        ]);
    }

    public function productLines()
    {
        return $this->hasMany(LumberInvoiceProductLine::class)->orderBy('sort_order');
    }
}
