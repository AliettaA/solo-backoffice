<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    protected $fillable = ['employee_id', 'paid_at', 'target_period', 'amount', 'method', 'memo', 'gross_amount', 'withholding_tax', 'social_insurance', 'resident_tax', 'dependents'];

    protected $casts = ['paid_at' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function syncJournalEntry(): void
    {
        $salaryCategoryName = $this->employee->employment_type === 'family' ? '給料' : '労務費';
        $salaryCategory = ExpenseCategory::firstOrCreate(['name' => $salaryCategoryName], ['sort_order' => 100]);
        $cashCategoryName = $this->method === '銀行振込' ? '銀行預金' : '現金';
        $cashCategory = ExpenseCategory::firstOrCreate(['name' => $cashCategoryName], ['sort_order' => 100]);

        if (! $salaryCategory || ! $cashCategory || $this->amount <= 0) {
            return;
        }

        $marker = '給与:salary_payment_'.$this->id;

        $entry = JournalEntry::where('description', $marker)->first()
            ?? new JournalEntry(['description' => $marker]);

        $entry->entry_date = $this->paid_at;
        $entry->save();

        $entry->lines()->delete();

        $entry->lines()->create([
            'side' => 'debit',
            'expense_category_id' => $salaryCategory->id,
            'counterparty_name' => $this->employee->name ?? null,
            'amount' => $this->gross_amount ?: $this->amount,
            'sort_order' => 0,
        ]);

        $entry->lines()->create([
            'side' => 'credit',
            'expense_category_id' => $cashCategory->id,
            'counterparty_name' => $this->employee->name ?? null,
            'amount' => $this->amount,
            'sort_order' => 1,
        ]);
        foreach (['源泉所得税預り金' => $this->withholding_tax, '預り金' => $this->social_insurance, '市県民税預り金' => $this->resident_tax] as $name => $amount) {
            if ($amount > 0) {
                $category = ExpenseCategory::firstOrCreate(['name' => $name], ['sort_order' => 100]);
                $entry->lines()->create([
                    'side' => 'credit', 'expense_category_id' => $category->id,
                    'counterparty_name' => $this->employee->name, 'amount' => $amount, 'sort_order' => 2,
                ]);
            }
        }

    }

    public function deleteJournalEntry(): void
    {
        $marker = '給与:salary_payment_'.$this->id;
        JournalEntry::where('description', $marker)->delete();
    }
}
