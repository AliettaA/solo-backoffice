<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    protected $fillable = [
        'estimate_number', 'client_id', 'title', 'issue_date',
        'expiry_date', 'status', 'notes',
        'subtotal', 'tax_amount', 'total',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(EstimateItem::class)->orderBy('sort_order');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('amount');
        $tax = (int) round($this->items->sum(fn ($i) => $i->amount * $i->tax_rate / 100));

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }
}
