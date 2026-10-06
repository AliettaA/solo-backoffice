<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use App\Models\SalaryPayment;
use App\Services\FinancialStatements;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialStatementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        DB::purge('sqlite');
        foreach (['*create_expense_categories*', '*create_opening_balances*', '*create_journal_entries*', '*create_journal_entry_lines*', '*create_employees*', '*create_attendances*', '*create_salary_payments*', '*add_dependents*', '*create_withholding_tax_brackets*', '*add_withholding_tax*', '*add_payroll_fields*', '*add_resident_tax*', '*add_statement_type*'] as $pattern) {
            foreach (glob(database_path('migrations/'.$pattern.'.php')) as $path) {
                (require $path)->up();
            }
        }
    }

    private function category(string $name): ExpenseCategory
    {
        return ExpenseCategory::firstOrCreate(['name' => $name], ['sort_order' => 0]);
    }

    private function entry(string $date, string $debit, string $credit, int $amount): void
    {
        $entry = JournalEntry::create(['entry_date' => $date]);
        foreach (['debit' => $debit, 'credit' => $credit] as $side => $name) {
            $entry->lines()->create(['side' => $side, 'expense_category_id' => $this->category($name)->id, 'amount' => $amount, 'sort_order' => 0]);
        }
    }

    private function opening(string $name, int $amount, string $date = '2025-01-01'): void
    {
        OpeningBalance::create(['expense_category_id' => $this->category($name)->id, 'effective_date' => $date, 'amount' => $amount]);
    }

    public function test_accrual_profit_opening_liability_and_year_boundaries(): void
    {
        $this->opening('現金', 100000);
        $this->opening('未払金', -20000);
        $this->opening('元入金', -80000);
        $this->entry('2025-01-01', '現金', '売上', 50000);
        $this->entry('2025-02-01', '未払金', '現金', 20000);
        $this->entry('2025-12-31', '通信費', '未払金', 5000);
        $this->entry('2026-01-01', '現金', '売上', 99000);
        $report = app(FinancialStatements::class)->build(2025);
        $this->assertSame(45000, $report['profit']);
        $this->assertSame(80000, $report['capital']);
        $this->assertSame(130000, $report['assetTotal']);
        $this->assertSame(0, $report['difference']);
        $this->get('/financial-statements?year=2025')->assertOk()->assertSee('45,000')->assertSee('貸借対照表');
        $next = app(FinancialStatements::class)->build(2026);
        $this->assertSame(125000, $next['capital']);
        $this->assertSame(99000, $next['profit']);
        $this->assertSame(0, $next['difference']);
        $this->get('/financial-statements?year=abc')->assertSessionHasErrors('year');
    }

    public function test_explicit_capital_mismatch_is_not_hidden_and_unknown_classification(): void
    {
        $this->opening('現金', 100000);
        $this->opening('元入金', -90000);
        $this->entry('2025-03-01', '現金', '独自収益', 7000);
        $report = app(FinancialStatements::class)->build(2025);
        $this->assertSame(10000, $report['openingDifference']);
        $this->assertSame(17000, $report['difference']);
        $category = $this->category('独自収益');
        $this->put('/financial-statements/categories/'.$category->id, ['statement_type' => 'revenue'])->assertSessionHasNoErrors();
        $report = app(FinancialStatements::class)->build(2025);
        $this->assertSame(10000, $report['difference']);
        $this->assertSame(7000, $report['profit']);
        $this->put('/financial-statements/categories/'.$category->id, ['statement_type' => 'invalid'])->assertSessionHasErrors('statement_type');
    }

    public function test_inventory_owner_drawings_and_family_salary_are_not_double_counted(): void
    {
        $this->opening('現金', 300000);
        $this->opening('商品', 20000);
        $this->entry('2025-01-01', '期首商品棚卸高', '商品', 20000);
        $this->entry('2025-04-01', '仕入', '現金', 40000);
        $this->entry('2025-12-31', '商品', '期末商品棚卸高', 10000);
        $this->entry('2025-06-01', '事業主貸', '現金', 30000);
        $this->entry('2025-06-02', '現金', '事業主借', 15000);
        $employee = Employee::create(['name' => '専従者', 'hourly_wage' => 0, 'employment_type' => 'family', 'dependents' => 0, 'is_active' => true]);
        $payment = SalaryPayment::create(['employee_id' => $employee->id, 'paid_at' => '2025-01-31', 'target_period' => '2025-01', 'amount' => 80000, 'gross_amount' => 80000, 'method' => '現金', 'withholding_tax' => 0, 'social_insurance' => 0, 'dependents' => 0]);
        $payment->syncJournalEntry();
        $report = app(FinancialStatements::class)->build(2025);
        $this->assertSame(50000, $report['cost']);
        $this->assertSame(80000, $report['family']);
        $this->assertSame(0, $report['expenses']);
        $this->assertSame(-130000, $report['profit']);
        $this->assertSame(0, $report['difference']);
        $next = app(FinancialStatements::class)->build(2026);
        $this->assertSame(175000, $next['capital']);
        $this->assertSame(0, $next['difference']);
    }

    public function test_opening_balance_form_accepts_liabilities_and_preserves_cash_sign(): void
    {
        $id = $this->category('未払金')->id;
        $this->post('/opening-balances', ['expense_category_id' => $id, 'effective_date' => '2025-01-01', 'amount' => 20000, 'balance_side' => 'credit'])->assertSessionHasNoErrors();
        $this->assertEquals(-20000, OpeningBalance::first()->amount);
        $this->get('/opening-balances')->assertOk()->assertSee('未払金');
        $this->opening('現金',100000,'2024-01-01');
        $this->entry('2024-12-31','通信費','現金',3000);
        $report = app(FinancialStatements::class)->build(2025);
        $this->assertSame(97000,$report['openingAssets']);
    }
}
