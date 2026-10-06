<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LumberInvoiceProductLine extends Model
{
    protected $fillable = [
        'lumber_invoice_id', 'product_id', 'description',
        'quantity', 'unit_price', 'tax_rate', 'amount', 'sort_order',
    ];

    public function invoice()
    {
        return $this->belongsTo(LumberInvoice::class, 'lumber_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}