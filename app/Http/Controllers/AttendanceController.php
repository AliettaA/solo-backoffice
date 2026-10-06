<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request, PayrollCalculator $calculator)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'employee_id' => ['nullable', 'integer', 'exists:employees,id']]);
        $selectedEmployeeId = $request->filled('employee_id') ? $request->integer('employee_id') : null;
        $tabEmployees = Employee::orderBy('name')->get();
        $selectedEmployee = $tabEmployees->firstWhere('id', $selectedEmployeeId);
        $month = $request->input('month') ?: now()->format('Y-m');
        $start = Carbon::createFromFormat('!Y-m', $month);
        $end = $start->copy()->endOfMonth();

        $attendances = Attendance::with('employee')
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->when($selectedEmployeeId, fn ($query) => $query->where('employee_id', $selectedEmployeeId))
            ->orderBy('work_date')
            ->get();

        $employeeSummary = Employee::where(function ($query) use ($attendances, $selectedEmployeeId) {
            $query->whereIn('id', $attendances->pluck('employee_id'))
                ->orWhere(fn ($family) => $family->where('employment_type', 'family')->where('is_active', true));
            if ($selectedEmployeeId) {
                $query->orWhere('id', $selectedEmployeeId);
            }
        })
            ->when($selectedEmployeeId, fn ($query) => $query->where('id', $selectedEmployeeId))
            ->orderBy('name')->get()->map(function ($employee) use ($attendances, $month, $end, $calculator) {
                $amount = $calculator->gross($employee, $month);
                $tax = null;
                $error = null;
                try {
                    $tax = $calculator->tax($amount, $employee->dependents, $end->toDateString());
                } catch (ValidationException $exception) {
                    $error = collect($exception->errors())->flatten()->first();
                }

                return [
                    'name' => $employee->name, 'dependents' => $employee->dependents,
                    'minutes' => $attendances->where('employee_id', $employee->id)->sum('worked_minutes'),
                    'amount' => $amount, 'tax' => $tax, 'net' => $tax === null ? null : $amount - $tax,
                    'error' => $error,
                ];
            });

        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return view('attendances.index', compact('attendances', 'month', 'employeeSummary', 'employees', 'tabEmployees', 'selectedEmployeeId', 'selectedEmployee'));
    }

    public function pdf(Request $request, PayrollCalculator $calculator)
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'employee_id' => ['required', 'integer', 'exists:employees,id']]);
        $employee = Employee::findOrFail($data['employee_id']);
        $month = $data['month'];
        $start = Carbon::createFromFormat('!Y-m', $month);
        $attendances = $employee->attendances()->whereBetween('work_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])->orderBy('work_date')->get();
        $payments = $employee->salaryPayments()->where('target_period', $month)->get();
        $registered = $payments->isNotEmpty();
        $workedGross = $calculator->gross($employee, $month);
        $gross = $registered ? $payments->sum(fn ($p) => $p->gross_amount ?: $p->amount) : $workedGross;
        $residentTax = $registered ? $payments->sum('resident_tax') : null;
        $insurance = $registered ? $payments->sum('social_insurance') : 0;
        $tax = $registered ? $payments->sum('withholding_tax') : null;
        $net = $registered ? $payments->sum('amount') : null;
        $minutes = $attendances->sum('worked_minutes');

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('attendances.pdf', compact('employee', 'month', 'attendances', 'registered', 'gross', 'workedGross', 'residentTax', 'insurance', 'tax', 'net', 'minutes'))
            ->setPaper('a4', 'portrait')->stream('attendance_'.$employee->id.'_'.$month.'.pdf');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $attendance = Attendance::create($data);
        $attendance->recalculate();

        return redirect()->route('attendances.index', ['month' => substr($data['work_date'], 0, 7), 'employee_id' => $request->input('selected_employee_id')])
            ->with('status', '勤怠を記録しました');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $this->validateData($request, $attendance);

        $attendance->update($data);
        $attendance->recalculate();

        return redirect()->route('attendances.index', ['month' => substr($data['work_date'], 0, 7), 'employee_id' => $request->input('selected_employee_id')])
            ->with('status', '勤怠を更新しました');
    }

    public function destroy(Request $request, Attendance $attendance)
    {
        $request->validate(['selected_employee_id' => ['nullable', 'integer', 'exists:employees,id']]);
        $month = $attendance->work_date->format('Y-m');
        $attendance->delete();

        return redirect()->route('attendances.index', ['month' => $month, 'employee_id' => $request->input('selected_employee_id')])
            ->with('status', '勤怠を削除しました');
    }

    private function validateData(Request $request, ?Attendance $attendance = null): array
    {
        $request->validate(['selected_employee_id' => ['nullable', 'integer', 'exists:employees,id']]);

        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['bail', 'required', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request, $attendance) {
                $exists = Attendance::where('employee_id', $request->input('employee_id'))
                    ->whereDate('work_date', $value)
                    ->when($attendance, fn ($query) => $query->where('id', '!=', $attendance->id))
                    ->exists();
                if ($exists) {
                    $fail('この従業員の同じ日付の勤怠は既に登録されています。既存の勤怠を編集してください。');
                }
            }],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'break_minutes' => ['nullable', 'integer', 'min:0'],
            'memo' => ['nullable', 'string'],
        ], [
            'employee_id.required' => '従業員を選択してください。',
            'employee_id.exists' => '有効な従業員を選択してください。',
            'work_date.required' => '勤務日を入力してください。',
            'start_time.required' => '開始時間を入力してください。',
            'start_time.date_format' => '開始時間を時:分の形式で入力してください。',
            'end_time.required' => '終了時間を入力してください。',
            'end_time.date_format' => '終了時間を時:分の形式で入力してください。',
            'end_time.after' => '終了時間は開始時間より後にしてください。',
            'break_minutes.integer' => '休憩時間は分単位の整数で入力してください。',
            'break_minutes.min' => '休憩時間は0分以上で入力してください。',
            'work_date.date_format' => '日付を正しい形式（年-月-日）で入力してください。',
        ]);
        $data['break_minutes'] = $data['break_minutes'] ?? 0;
        $start = Carbon::createFromFormat('!H:i', $data['start_time']);
        $end = Carbon::createFromFormat('!H:i', $data['end_time']);
        if ($data['break_minutes'] > $start->diffInMinutes($end)) {
            throw ValidationException::withMessages(['break_minutes' => '休憩時間は開始から終了までの時間以内で入力してください。']);
        }
        return $data;
    }
}
