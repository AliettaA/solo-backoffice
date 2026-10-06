<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LumberInvoiceLine extends Model
{
    protected $fillable = [
        'lumber_invoice_id', 'lumber_species_id', 'thickness', 'width', 'length',
        'returned_quantity', 'unit_price_adjustment', 'adjustment_amount', 'total_quantity', 'line_total', 'sort_order',
    ];

    public function invoice()
    {
        return $this->belongsTo(LumberInvoice::class, 'lumber_invoice_id');
    }

    public function species()
    {
        return $this->belongsTo(LumberSpecies::class, 'lumber_species_id');
    }

    public function tiers()
    {
        return $this->hasMany(LumberInvoiceLineTier::class);
    }

    public function recalculate(): void
    {
        $totalQty = $this->tiers->sum('quantity') + (int) $this->returned_quantity;
        $tierAmount = $this->tiers->sum('amount');

        if ($tierAmount + $this->adjustment_amount < 0) {
            throw ValidationException::withMessages(['lines' => '明細の金額調整後の合計は0円以上にしてください。']);
        }
        $this->update([
            'total_quantity' => $totalQty,
            'line_total' => $tierAmount + $this->adjustment_amount,
        ]);
    }
}
