<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name')->get();

        return view('employees.index', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('employees', 'name')],
            'hourly_wage' => ['required_if:employment_type,employee', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'employment_type' => ['required', 'in:employee,family'],
            'dependents' => ['required', 'integer', 'min:0', 'max:7'],
        ]);

        $data['hourly_wage'] = $data['employment_type'] === 'family' ? 0 : ($data['hourly_wage'] ?? 0);
        Employee::create($data);

        return back()->with('status', '従業員を追加しました');
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('employees', 'name')->ignore($employee->id)],
            'hourly_wage' => ['required_if:employment_type,employee', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'employment_type' => ['required', 'in:employee,family'],
            'dependents' => ['required', 'integer', 'min:0', 'max:7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $data['hourly_wage'] = $data['employment_type'] === 'family' ? 0 : ($data['hourly_wage'] ?? 0);
        $employee->update($data);

        return back()->with('status', '従業員を更新しました');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->attendances()->exists() || $employee->salaryPayments()->exists()) {
            throw ValidationException::withMessages(['name' => '勤怠・給与で使用中の従業員は削除できません。退職の場合は在籍のチェックを外してください。']);
        }
        $employee->delete();

        return back()->with('status', '従業員を削除しました');
    }
}
