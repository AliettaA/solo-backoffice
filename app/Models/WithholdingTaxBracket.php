<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingTaxBracket extends Model
{
    protected $fillable = [
        'tax_year', 'min_amount', 'max_amount',
        'dep_0', 'dep_1', 'dep_2', 'dep_3', 'dep_4', 'dep_5', 'dep_6', 'dep_7',
    ];
}
