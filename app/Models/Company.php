<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'representative_name', 'postal_code', 'address',
        'phone', 'email', 'invoice_registration_number',
        'bank_name', 'bank_branch', 'bank_account_type',
        'bank_account_number', 'bank_account_holder', 'seal_image_path',
    ];
}