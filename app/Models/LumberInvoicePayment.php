<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LumberInvoicePayment extends Model
{
    protected $fillable = ['lumber_invoice_id', 'paid_at', 'amount', 'method', 'memo'];

    protected $casts = ['paid_at' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(LumberInvoice::class, 'lumber_invoice_id');
    }
}