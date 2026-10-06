<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\WithholdingTaxBracket;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PayrollCalculator
{
    public function gross(Employee $employee, string $month): int
    {
        if ($employee->employment_type === 'family') {
            return $employee->is_active ? 80000 : 0;
        }
        $start = Carbon::createFromFormat('!Y-m', $month);

        return (int) $employee->attendances()->whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])->sum('amount');
    }

    public function tax(int $amount, int $dependents, string $paidAt): int
    {
        $year = (int) substr($paidAt, 0, 4);
        if (! in_array($year, [2025, 2026], true)) {
            throw ValidationException::withMessages(['paid_at' => '自動計算は2025年・2026年の月額表・甲欄に対応しています。']);
        }
        if ($dependents < 0 || $dependents > 7 || $amount < 0) {
            throw ValidationException::withMessages(['amount' => '扶養人数または控除後の給与額を確認してください。']);
        }
        if ($amount < ($year === 2025 ? 88000 : 105000)) {
            return 0;
        }
        $bracket = WithholdingTaxBracket::where('tax_year', $year)->where('min_amount', '<=', $amount)->where('max_amount', '>', $amount)->first();
        if (! $bracket) {
            throw ValidationException::withMessages(['amount' => 'この給与額の税額表が未登録です。自動計算の対応範囲は社会保険料等控除後332,000円未満です。']);
        }

        return (int) $bracket->{'dep_'.$dependents};
    }
}
