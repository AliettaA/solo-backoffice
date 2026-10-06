<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'description', 'unit_price', 'unit',
        'tax_rate', 'is_reduced_tax',
    ];

    protected $casts = [
        'is_reduced_tax' => 'boolean',
    ];
}
