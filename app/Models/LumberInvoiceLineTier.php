<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LumberInvoiceLineTier extends Model
{
    protected $fillable = [
        'lumber_invoice_line_id', 'tier_label', 'unit_price', 'quantity', 'amount',
    ];

    public function line()
    {
        return $this->belongsTo(LumberInvoiceLine::class, 'lumber_invoice_line_id');
    }
}