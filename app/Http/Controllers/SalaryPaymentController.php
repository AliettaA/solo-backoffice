<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryPaymentController extends Controller
{
    public function index(Request $request, PayrollCalculator $calculator)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->input('month') ?: now()->format('Y-m');
        $defaultPaidAt = Carbon::createFromFormat('!Y-m', $month)->endOfMonth()->toDateString();
        $summaries = Employee::orderBy('name')->get()->map(function ($employee) use ($month, $calculator, $defaultPaidAt) {
            $payments = $employee->salaryPayments()->where('target_period', $month)->orderBy('paid_at')->get();
            $gross = $calculator->gross($employee, $month);
            $paid = $payments->sum(fn ($payment) => $payment->gross_amount ?: $payment->amount);
            $tax = null;
            $error = null;
            try {
                $tax = $calculator->tax($gross, $employee->dependents, $defaultPaidAt);
            } catch (ValidationException $e) {
                $error = collect($e->errors())->flatten()->first();
            }

            return ['employee' => $employee, 'worked_amount' => $gross, 'paid_amount' => $paid,
                'remaining' => $gross - $paid, 'payments' => $payments, 'tax' => $tax, 'error' => $error];
        })->filter(fn ($s) => $s['worked_amount'] > 0 || $s['payments']->isNotEmpty())->values();

        return view('salary_payments.index', compact('summaries', 'month', 'defaultPaidAt'));
    }

    public function store(Request $request, PayrollCalculator $calculator)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'target_period' => ['required', 'date_format:Y-m'],
            'social_insurance' => ['sometimes', 'integer', 'min:0'],
            'resident_tax' => ['sometimes', 'integer', 'min:0'],
            'method' => ['required', 'in:銀行振込,現金'],
            'memo' => ['nullable', 'string'],
        ]);
        $data += ['social_insurance' => 0, 'resident_tax' => 0];
        DB::transaction(function () use ($data, $calculator) {
            $employee = Employee::lockForUpdate()->findOrFail($data['employee_id']);
            if ($employee->salaryPayments()->where('target_period', $data['target_period'])->exists()) {
                throw ValidationException::withMessages(['target_period' => 'この月は支払記録があります。修正する場合は既存の支払いを削除してから登録してください。']);
            }
            $gross = $calculator->gross($employee, $data['target_period']);
            if ($gross <= 0 || $data['social_insurance'] >= $gross) {
                throw ValidationException::withMessages(['social_insurance' => '支給額と社会保険料等を確認してください。控除額は支給額未満にしてください。']);
            }
            $tax = $calculator->tax($gross - $data['social_insurance'], $employee->dependents, $data['paid_at']);
            if ($gross - $data['social_insurance'] - $tax - $data['resident_tax'] <= 0) {
                throw ValidationException::withMessages(['resident_tax' => '市県民税を差し引いた支給額が0円以下になります。入力額を確認してください。']);
            }
            $payment = SalaryPayment::create($data + [
                'gross_amount' => $gross, 'withholding_tax' => $tax,
                'dependents' => $employee->dependents,
                'amount' => $gross - $data['social_insurance'] - $tax - $data['resident_tax'],
            ]);
            $payment->syncJournalEntry();
        });

        return redirect()->route('salary-payments.index', ['month' => $data['target_period']])->with('status', '源泉徴収額を計算して給与支払いを記録しました');
    }

    public function destroy(SalaryPayment $salaryPayment)
    {
        $month = $salaryPayment->target_period;
        DB::transaction(function () use ($salaryPayment) {
            $salaryPayment->deleteJournalEntry();
            $salaryPayment->delete();
        });

        return redirect()->route('salary-payments.index', ['month' => $month])->with('status', '給与支払いを削除しました');
    }

    public function preview(Request $request, PayrollCalculator $calculator)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'], 'target_period' => ['required', 'date_format:Y-m'],
            'paid_at' => ['required', 'date_format:Y-m-d'], 'social_insurance' => ['sometimes', 'integer', 'min:0'],
            'resident_tax' => ['sometimes', 'integer', 'min:0'],
        ]);
        $data += ['social_insurance' => 0, 'resident_tax' => 0];
        $employee = Employee::findOrFail($data['employee_id']);
        $gross = $calculator->gross($employee, $data['target_period']);
        if ($data['social_insurance'] >= $gross) {
            throw ValidationException::withMessages(['social_insurance' => '社会保険料等は支給額未満にしてください。']);
        }
        $tax = $calculator->tax($gross - $data['social_insurance'], $employee->dependents, $data['paid_at']);

        $net = $gross - $data['social_insurance'] - $tax - $data['resident_tax'];
        if ($net <= 0) {
            throw ValidationException::withMessages(['resident_tax' => '市県民税を差し引いた支給額が0円以下になります。入力額を確認してください。']);
        }
        return response()->json(['tax' => $tax, 'net' => $net]);
    }
}
