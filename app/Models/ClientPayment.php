<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPayment extends Model
{
    protected $fillable = ['client_id', 'paid_at', 'amount', 'target_month', 'method', 'memo'];

    protected $casts = ['paid_at' => 'date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function syncJournalEntry(): void
    {
        if ($this->method !== '銀行振込' && $this->method !== '現金') {
            return; // 「その他」は自動仕訳しない
        }

        $salesCategory = \App\Models\ExpenseCategory::firstOrCreate(['name' => '売上'], ['sort_order' => 100]);
        $cashCategoryName = $this->method === '銀行振込' ? '銀行預金' : '現金';
        $cashCategory = \App\Models\ExpenseCategory::firstOrCreate(['name' => $cashCategoryName], ['sort_order' => 100]);

        if (! $salesCategory || ! $cashCategory || $this->amount <= 0) {
            return;
        }

        $marker = '入金:client_payment_' . $this->id;

        $entry = \App\Models\JournalEntry::where('description', $marker)->first()
            ?? new \App\Models\JournalEntry(['description' => $marker]);

        $entry->entry_date = $this->paid_at;
        $entry->save();

        $entry->lines()->delete();

        $entry->lines()->create([
            'side' => 'debit',
            'expense_category_id' => $cashCategory->id,
            'counterparty_name' => $this->client->name ?? null,
            'amount' => $this->amount,
            'sort_order' => 0,
        ]);

        $entry->lines()->create([
            'side' => 'credit',
            'expense_category_id' => $salesCategory->id,
            'counterparty_name' => $this->client->name ?? null,
            'amount' => $this->amount,
            'sort_order' => 1,
        ]);
    }

    public function deleteJournalEntry(): void
    {
        $marker = '入金:client_payment_' . $this->id;
        \App\Models\JournalEntry::where('description', $marker)->delete();
    }
}