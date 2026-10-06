<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuantityTierSurcharge extends Model
{
    protected $fillable = ['label', 'min_qty', 'max_qty', 'surcharge_amount', 'sort_order'];
}
