<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LumberSpecies extends Model
{
    protected $fillable = [
        'name', 'pricing_type', 'flat_price',
        'apply_thickness_surcharge', 'apply_length_adjustment',
    ];

    protected $casts = [
        'apply_thickness_surcharge' => 'boolean',
        'apply_length_adjustment' => 'boolean',
    ];

    public function widthPrices()
    {
        return $this->hasMany(LumberWidthPrice::class);
    }
}