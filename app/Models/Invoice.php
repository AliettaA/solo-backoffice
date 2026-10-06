<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'client_id', 'estimate_id', 'title',
        'issue_date', 'due_date', 'status', 'notes',
        'subtotal_10', 'tax_10', 'subtotal_8', 'tax_8',
        'total', 'paid_amount',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function recalculateTotals(): void
    {
        $items10 = $this->items->where('tax_rate', 10);
        $items8 = $this->items->where('tax_rate', 8);

        $subtotal10 = $items10->sum('amount');
        $subtotal8 = $items8->sum('amount');
        $tax10 = (int) round($subtotal10 * 0.10);
        $tax8 = (int) round($subtotal8 * 0.08);

        $this->update([
            'subtotal_10' => $subtotal10,
            'tax_10' => $tax10,
            'subtotal_8' => $subtotal8,
            'tax_8' => $tax8,
            'total' => $subtotal10 + $tax10 + $subtotal8 + $tax8,
        ]);
    }

    public function recalculatePaidAmount(): void
    {
        $paid = $this->payments->sum('amount');
        $status = $this->status;

        if ($paid <= 0) {
            $status = $this->due_date?->isPast() ? 'overdue' : ($status === 'draft' ? 'draft' : 'sent');
        } elseif ($paid < $this->total) {
            $status = 'partially_paid';
        } else {
            $status = 'paid';
        }

        $this->update(['paid_amount' => $paid, 'status' => $status]);
    }

    public function remainingAmount(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }
}
