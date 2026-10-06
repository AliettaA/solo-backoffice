<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id', 'description', 'quantity', 'unit',
        'unit_price', 'tax_rate', 'amount', 'sort_order',
    ];

    public function estimate()
    {
        return $this->belongsTo(Estimate::class);
    }
}