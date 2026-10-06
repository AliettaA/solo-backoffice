<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThicknessSurcharge extends Model
{
    protected $fillable = ['min_thickness', 'surcharge_amount'];
}