<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'work_date',
        'start_time',
        'end_time',
        'break_minutes',
        'worked_minutes',
        'amount',
        'memo',
    ];

    protected $casts = ['work_date' => 'date'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function recalculate(): void
    {
        $start = Carbon::parse($this->work_date->format('Y-m-d') . ' ' . $this->start_time);
        $end = Carbon::parse($this->work_date->format('Y-m-d') . ' ' . $this->end_time);

        $totalMinutes = abs($end->diffInMinutes($start));
        $workedMinutes = max(0, $totalMinutes - $this->break_minutes);

        $hourlyWage = $this->employee->hourly_wage;
        $amount = (int) round($workedMinutes / 60 * $hourlyWage);

        $this->update([
            'worked_minutes' => $workedMinutes,
            'amount' => $amount,
        ]);
    }
}