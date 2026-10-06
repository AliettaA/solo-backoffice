<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LumberWidthPrice extends Model
{
    protected $fillable = ['lumber_species_id', 'width', 'base_price'];

    public function species()
    {
        return $this->belongsTo(LumberSpecies::class, 'lumber_species_id');
    }
}