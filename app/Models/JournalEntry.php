<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = ['entry_date', 'description'];

    protected $casts = ['entry_date' => 'date'];

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('sort_order');
    }

    public function debitLines()
    {
        return $this->lines->where('side', 'debit');
    }

    public function creditLines()
    {
        return $this->lines->where('side', 'credit');
    }

    public function totalAmount(): int
    {
        return $this->debitLines()->sum('amount');
    }
}