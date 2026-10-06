<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name', 'honorific', 'postal_code', 'address',
        'contact_person', 'email', 'phone', 'memo', 'invoice_registration_number',
    ];

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(ClientPayment::class);
    }
}
