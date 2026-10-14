<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name', 'honorific', 'postal_code', 'address',
        'contact_person', 'email', 'phone', 'memo', 'invoice_registration_number',
    ];

    public function payments()
    {
        return $this->hasMany(ClientPayment::class);
    }
}
