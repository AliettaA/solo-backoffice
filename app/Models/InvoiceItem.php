<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit',
        'unit_price', 'tax_rate', 'amount', 'sort_order',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
 //
}
