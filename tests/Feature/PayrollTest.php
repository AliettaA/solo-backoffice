<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\SalaryPayment;
use App\Services\PayrollCalculator;
use Database\Seeders\WithholdingTaxBracketSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        // Run the actual payroll/accounting migrations; unrelated legacy migrations require MySQL.
        foreach (['*create_expense_categories*', '*create_opening_balances*', '*create_journal_entries*', '*create_journal_entry_lines*', '*create_employees*', '*create_attendances*', '*create_salary_payments*', '*add_dependents*', '*create_withholding_tax_brackets*', '*add_withholding_tax*', '*add_payroll_fields*', '*add_resident_tax*', '*add_year_to_withholding_tax_brackets*'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern.'.php')) as $path) {
                (require $path)->up();
            }
        }
        $this->seed(WithholdingTaxBracketSeeder::class);
        $this->travelTo(now()->setDate(2026, 9, 20));
    }

    public function test_attendance_pdf_uses_selected_month_and_recorded_payment(): void
    {
        $employee = $this->employee();
        $employee->attendances()->create(['work_date' => '2026-09-01', 'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60, 'worked_minutes' => 480, 'amount' => 8000]);
        $employee->attendances()->create(['work_date' => '2026-08-01', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 99999]);
        SalaryPayment::create(['employee_id' => $employee->id, 'target_period' => '2026-09', 'paid_at' => '2026-10-01', 'gross_amount' => 8000, 'amount' => 6700, 'resident_tax' => 1000, 'withholding_tax' => 300, 'method' => '現金']);
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->with('attendances.pdf', \Mockery::on(function ($data) {
            $this->assertCount(1, $data['attendances']);
            $this->assertEquals(480, $data['minutes']);
            $this->assertEquals(1000, $data['residentTax']);
            $this->assertEquals(300, $data['tax']);
            $this->assertEquals(6700, $data['net']);
            $this->assertEquals(8000, $data['gross']);
            return true;
        }))->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->with('a4', 'portrait')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('PDF'));
        $this->get('/attendances/pdf?month=2026-09&employee_id='.$employee->id)->assertOk();
        $this->get('/attendances/pdf?month=invalid&employee_id='.$employee->id)->assertSessionHasErrors('month');
        $this->get('/attendances/pdf?month=2026-09')->assertSessionHasErrors('employee_id');
    }

    public function test_attendance_time_validation_on_create_and_update(): void
    {
        $employee = $this->employee();
        $data = ['employee_id' => $employee->id, 'work_date' => '2026-09-01', 'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60];
        foreach ([['end_time', '07:00'], ['end_time', '08:00'], ['start_time', '25:00'], ['break_minutes', 541], ['break_minutes', -1], ['break_minutes', '1.5']] as [$key, $value]) {
            $this->post('/attendances', array_replace($data, [$key => $value]))->assertSessionHasErrors($key);
        }
        $this->assertSame(0, \App\Models\Attendance::count());
        $this->post('/attendances', $data)->assertSessionHasNoErrors();
        $attendance = \App\Models\Attendance::firstOrFail();
        $this->assertEquals(480, $attendance->worked_minutes);
        $this->put('/attendances/'.$attendance->id, array_replace($data, ['break_minutes' => 600]))->assertSessionHasErrors('break_minutes');
        $this->assertEquals(480, $attendance->fresh()->worked_minutes);
        $this->put('/attendances/'.$attendance->id, $data)->assertSessionHasNoErrors();
        $this->post('/attendances', $data)->assertSessionHasErrors('work_date');
        $this->post('/attendances', array_replace($data, ['work_date' => '2026-09-02', 'break_minutes' => null]))->assertSessionHasNoErrors();
        $this->assertEquals(540, \App\Models\Attendance::orderByDesc('id')->first()->worked_minutes);
    }

    private function employee(array $attributes = []): Employee
    {
        return Employee::create($attributes + ['name' => 'テスト従業員', 'hourly_wage' => 1000, 'employment_type' => 'employee', 'dependents' => 0, 'is_active' => true]);
    }

    private function payload(Employee $employee): array
    {
        return ['employee_id' => $employee->id, 'target_period' => '2026-09', 'paid_at' => '2026-09-25', 'social_insurance' => 0, 'method' => '銀行振込'];
    }

    public function test_resident_tax_is_deducted_after_withholding_and_journal_balances(): void
    {
        $employee = $this->employee(['dependents' => 1]);
        $employee->attendances()->create(['work_date' => '2026-09-01', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 160000]);
        $data = array_replace($this->payload($employee), ['social_insurance' => 10000, 'resident_tax' => 5000]);
        $this->getJson('/salary-payments/preview?'.http_build_query($data))->assertOk()->assertJson(['tax' => 810, 'net' => 144190]);
        $this->post('/salary-payments', $data)->assertSessionHasNoErrors();
        $payment = SalaryPayment::firstOrFail();
        $this->assertEquals(810, $payment->withholding_tax);
        $this->assertEquals(5000, $payment->resident_tax);
        $this->assertEquals(144190, $payment->amount);
        $entry = JournalEntry::firstOrFail();
        $this->assertEquals(160000, $entry->lines()->where('side', 'credit')->sum('amount'));
        $this->assertEquals(5000, $entry->lines()->whereHas('category', fn ($q) => $q->where('name', '市県民税預り金'))->sum('amount'));
        $this->assertSame('liability', \App\Services\FinancialStatements::defaultType('市県民税預り金'));
        $this->delete('/salary-payments/'.$payment->id);
        unset($data['social_insurance']);
        $data['resident_tax'] = 160000;
        $this->getJson('/salary-payments/preview?'.http_build_query($data))->assertUnprocessable()->assertJsonValidationErrors('resident_tax');
        $this->post('/salary-payments', $data)->assertSessionHasErrors('resident_tax');
        $this->assertSame(0, SalaryPayment::count());
        $data['resident_tax'] = -1;
        $this->post('/salary-payments', $data)->assertSessionHasErrors('resident_tax');
        $data['resident_tax'] = 5000;
        $this->post('/salary-payments', $data)->assertSessionHasNoErrors();
        $payment = SalaryPayment::firstOrFail();
        $this->assertEquals(app(PayrollCalculator::class)->tax(160000, 1, '2026-09-25'), $payment->withholding_tax);
        $this->assertEquals(160000 - $payment->withholding_tax - 5000, $payment->amount);
    }

    public function test_family_salary_without_attendance_and_duplicate_protection(): void
    {
        $employee = $this->employee(['employment_type' => 'family']);
        $this->get('/salary-payments?month=2026-09')->assertOk()->assertSee('80,000');
        $this->post('/salary-payments', $this->payload($employee))->assertSessionHasNoErrors();
        $payment = SalaryPayment::firstOrFail();
        $this->assertEquals('給料', JournalEntry::firstOrFail()->lines()->where('side', 'debit')->firstOrFail()->category->name);
        $this->assertEquals(80000, $payment->gross_amount);
        $this->assertEquals(0, $payment->withholding_tax);
        $this->assertEquals(80000, $payment->amount);
        $this->post('/salary-payments', $this->payload($employee))->assertSessionHasErrors('target_period');
        $this->assertEquals(1, SalaryPayment::count());
        $this->assertEquals(1, JournalEntry::count());
    }

    public function test_employee_deductions_preview_and_balanced_journal(): void
    {
        $employee = $this->employee(['dependents' => 1]);
        $employee->attendances()->create(['work_date' => '2026-09-01', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 160000]);
        $data = array_replace($this->payload($employee), ['social_insurance' => 10000]);
        $this->getJson('/salary-payments/preview?'.http_build_query($data))->assertOk()->assertJson(['tax' => 810, 'net' => 149190]);
        $this->post('/salary-payments', $data + ['amount' => 1, 'withholding_tax' => 0])->assertSessionHasNoErrors();
        $payment = SalaryPayment::firstOrFail();
        $this->assertEquals('労務費', JournalEntry::firstOrFail()->lines()->where('side', 'debit')->firstOrFail()->category->name);
        $this->assertEquals(160000, $payment->gross_amount);
        $this->assertEquals(810, $payment->withholding_tax);
        $this->assertEquals(149190, $payment->amount);
        $entry = JournalEntry::firstOrFail();
        $this->assertEquals(160000, $entry->lines()->where('side', 'debit')->sum('amount'));
        $this->assertEquals(160000, $entry->lines()->where('side', 'credit')->sum('amount'));
        $this->assertEquals(4, $entry->lines()->count());
        $this->delete('/salary-payments/'.$payment->id)->assertSessionHasNoErrors();
        $this->assertEquals(0, JournalEntry::count());
        $this->assertEquals(0, DB::table('journal_entry_lines')->count());
    }

    public function test_tax_boundaries_and_dependents(): void
    {
        $calculator = app(PayrollCalculator::class);
        foreach ([[104999, 0, 0], [105000, 0, 170], [106999, 0, 170], [107000, 0, 280], [150000, 0, 2420], [150000, 1, 810], [150000, 2, 0], [331999, 0, 10020]] as [$amount,$dependents,$tax]) {
            $this->assertSame($tax, $calculator->tax($amount, $dependents, '2026-09-25'));
        }
        $this->expectException(ValidationException::class);
        $calculator->tax(332000, 0, '2026-09-25');
    }

    public function test_unsupported_year_and_invalid_deduction_do_not_save(): void
    {
        $employee = $this->employee(['employment_type' => 'family']);
        $this->post('/salary-payments', array_replace($this->payload($employee), ['paid_at' => '2027-01-25']))->assertSessionHasErrors('paid_at');
        $this->post('/salary-payments', array_replace($this->payload($employee), ['social_insurance' => 80000]))->assertSessionHasErrors('social_insurance');
        $this->assertEquals(0, SalaryPayment::count());
    }

    public function test_employee_fields_and_legacy_payment(): void
    {
        $this->post('/employees', ['name' => '専従者', 'employment_type' => 'family', 'dependents' => 2])->assertSessionHasNoErrors();
        $employee = Employee::firstOrFail();
        $this->assertEquals('family', $employee->employment_type);
        $this->assertEquals(2, $employee->dependents);
        $this->get('/employees')->assertOk()->assertSee('扶養人数');
        SalaryPayment::create($this->payload($employee) + ['amount' => 80000]);
        $this->get('/salary-payments?month=2026-09')->assertOk()->assertSee('登録済み');
        $this->put('/employees/'.$employee->id, ['name' => '更新', 'hourly_wage' => 1200, 'employment_type' => 'employee', 'dependents' => 3, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertEquals(3, $employee->fresh()->dependents);
    }

    public function test_2025_attendance_and_payment_use_the_correct_year(): void
    {
        $employee = $this->employee(['dependents' => 1]);
        $employee->attendances()->create(['work_date' => '2025-01-15', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 150000]);
        $this->get('/attendances?month=2025-01')->assertOk()->assertSee('2025年')->assertSee('1,360')->assertSee('148,640');
        $this->get('/salary-payments?month=2025-01')->assertOk()->assertSee('2025-01-31')->assertSee('1,360');
        $this->assertSame(0, SalaryPayment::count());
        $this->post('/salary-payments', array_replace($this->payload($employee), ['target_period' => '2025-01', 'paid_at' => '2025-02-10']))->assertSessionHasNoErrors();
        $this->assertEquals(1360, SalaryPayment::firstOrFail()->withholding_tax);
        $this->assertEquals(148640, SalaryPayment::firstOrFail()->amount);
        $calculator = app(PayrollCalculator::class);
        $this->assertSame(0, $calculator->tax(87999, 0, '2025-01-31'));
        $this->assertSame(130, $calculator->tax(88000, 0, '2025-01-31'));
        $this->assertSame(2980, $calculator->tax(150000, 0, '2025-01-31'));
        $this->assertSame(2420, $calculator->tax(150000, 0, '2026-01-31'));
        $this->assertSame(0, $calculator->tax(80000, 0, '2025-01-31'));
    }

    public function test_attendance_tabs_filter_by_employee_and_keep_selection(): void
    {
        $first = $this->employee(['name' => '同姓同名']);
        $second = $this->employee(['name' => '同姓同名']);
        foreach ([$first, $second] as $employee) {
            $employee->attendances()->create(['work_date' => '2025-01-15', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 8000]);
        }
        $url = '/attendances?month=2025-01&employee_id='.$first->id;
        $this->get($url)->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->count() === 1 && $rows->first()->employee_id === $first->id)
            ->assertViewHas('employeeSummary', fn ($rows) => $rows->count() === 1)
            ->assertSee('name="employee_id" value="'.$first->id.'"', false);
        $this->get('/attendances?month=2025-02&employee_id='.$first->id)->assertOk()
            ->assertViewHas('attendances', fn ($rows) => $rows->isEmpty())->assertViewHas('selectedEmployeeId', $first->id);
        $this->get('/attendances?month=2025-01')->assertOk()->assertViewHas('attendances', fn ($rows) => $rows->count() === 2);
        $data = ['employee_id' => $first->id, 'selected_employee_id' => $first->id, 'work_date' => '2025-01-16', 'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60];
        $this->post('/attendances', $data)->assertRedirect($url);
        $attendance = $first->attendances()->latest('id')->first();
        $this->put('/attendances/'.$attendance->id, $data)->assertRedirect($url);
        $this->delete('/attendances/'.$attendance->id, ['selected_employee_id' => $first->id])->assertRedirect($url);
    }

    public function test_cash_book_displays_only_net_salary(): void
    {
        $employee = $this->employee();
        $employee->attendances()->create(['work_date' => '2025-01-15', 'start_time' => '08:00', 'end_time' => '17:00', 'amount' => 140800]);
        $this->post('/salary-payments', array_replace($this->payload($employee), ['target_period' => '2025-01', 'paid_at' => '2025-01-31', 'method' => '現金']))->assertSessionHasNoErrors();
        $this->get('/cash-book?month=2025-01')->assertOk()->assertSee('138,120')->assertDontSee('源泉所得税預り金')->assertDontSee('参考額・現金支出なし')
            ->assertViewHas('book', fn ($book) => count($book['rows']) === 1
                && (int) $book['rows'][0]['expense'] === 138120
                && (int) $book['closing'] === -138120);
        $this->assertEquals(1, JournalEntry::count());
    }

    public function test_cash_book_displays_family_salary_once(): void
    {
        $employee = $this->employee(['employment_type' => 'family']);
        $this->post('/salary-payments', array_replace($this->payload($employee), ['method' => '現金']))->assertSessionHasNoErrors();
        $this->get('/cash-book?month=2026-09')->assertOk()->assertSee('80,000')->assertDontSee('源泉所得税預り金')
            ->assertViewHas('book', fn ($book) => (int) $book['closing'] === -80000);
    }

    public function test_cash_and_bank_totals_include_opening_and_carry_forward(): void
    {
        foreach (['現金', '銀行預金'] as $name) {
            $category = \App\Models\ExpenseCategory::create(['name' => $name, 'sort_order' => 1]);
            DB::table('opening_balances')->insert(['expense_category_id' => $category->id, 'effective_date' => '2025-01-01', 'amount' => 10000]);
            foreach ([['debit', 3000], ['credit', 4000]] as [$side, $amount]) {
                $entry = JournalEntry::create(['entry_date' => '2025-01-15', 'description' => '集計テスト']);
                $entry->lines()->create(['side' => $side, 'expense_category_id' => $category->id, 'amount' => $amount, 'sort_order' => 0]);
            }
            $this->get('/cash-book?'.http_build_query(['type' => $name, 'month' => '2025-01', 'year' => '2025']))->assertOk()
                ->assertSee('一致しています')->assertSee($name === '現金' ? '次月繰越' : '翌年繰越')
                ->assertViewHas('book', fn ($book) => $book['income_total'] == 3000 && $book['expense_total'] == 4000
                    && $book['closing'] == 9000 && $book['receipt_total'] == 13000 && $book['payment_total'] == 13000 && $book['balanced']);
        }
    }

    public function test_attendance_rejects_duplicate_employee_date_on_create_and_update(): void
    {
        $employee = $this->employee();
        $other = $this->employee(['name' => '別の従業員']);
        $data = ['employee_id' => $employee->id, 'work_date' => '2025-01-15', 'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60];
        $this->post('/attendances', $data)->assertSessionHasNoErrors();
        $first = $employee->attendances()->firstOrFail();
        $this->post('/attendances', $data)->assertSessionHasErrors('work_date');
        $this->assertEquals(1, $employee->attendances()->count());
        $this->put('/attendances/'.$first->id, $data)->assertSessionHasNoErrors();
        $this->post('/attendances', array_replace($data, ['employee_id' => $other->id]))->assertSessionHasNoErrors();
        $this->post('/attendances', array_replace($data, ['work_date' => '2025-01-16']))->assertSessionHasNoErrors();
        $second = $employee->attendances()->orderByDesc('id')->firstOrFail();
        $this->put('/attendances/'.$second->id, $data)->assertSessionHasErrors('work_date');
        $this->assertSame('2025-01-16', $second->fresh()->work_date->format('Y-m-d'));
        $this->put('/attendances/'.$first->id, array_replace($data, ['employee_id' => $other->id]))->assertSessionHasErrors('work_date');
        $this->assertEquals($employee->id, $first->fresh()->employee_id);
    }

    public function test_tax_seed_can_be_repeated(): void
    {
        $count = DB::table('withholding_tax_brackets')->count();
        $this->seed(WithholdingTaxBracketSeeder::class);
        $this->assertEquals($count, DB::table('withholding_tax_brackets')->count());
    }
}
