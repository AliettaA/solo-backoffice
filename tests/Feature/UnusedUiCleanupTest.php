<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UnusedUiCleanupTest extends InvoicePaymentRetirementTest
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
    }

    private function columnMigration(): object
    {
        return require database_path('migrations/2026_10_16_000002_remove_unused_lumber_invoice_paid_amount.php');
    }

    public function test_column_retirement_preserves_other_values_and_rollback_restores_zero(): void
    {
        $before = DB::table('lumber_invoices')->first();
        unset($before->paid_amount);
        $receipts = DB::table('client_payments')->get()->toJson();
        $m = $this->columnMigration();
        $m->up();
        $this->assertFalse(Schema::hasColumn('lumber_invoices', 'paid_amount'));
        $this->assertEquals($before, DB::table('lumber_invoices')->first());
        $this->assertSame($receipts, DB::table('client_payments')->get()->toJson());
        $m->up();
        $m->down();
        $this->assertSame(0, DB::table('lumber_invoices')->value('paid_amount'));
    }

    public function test_nonzero_paid_amount_blocks_column_deletion(): void
    {
        DB::table('lumber_invoices')->update(['paid_amount' => 100]);
        try {
            $this->columnMigration()->up();
            $this->fail('Nonzero value must not be lost.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('削除を中止', $e->getMessage());
        }
        $this->assertSame(100, DB::table('lumber_invoices')->value('paid_amount'));
    }

    public function test_all_business_routes_have_implemented_methods_and_unused_get_routes_are_unavailable(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            $action = $route->getActionName();
            if (str_starts_with($action, 'App\\Http\\Controllers\\')) {
                [$class, $method] = explode('@', $action);
                $this->assertTrue(method_exists($class, $method), $action);
            }
        }
        foreach (['employees/create', 'employees/1/edit', 'employees/1', 'clients/1', 'products/1', 'journal-entries/1', 'lumber-invoices/1'] as $path) {
            $this->assertContains($this->get('/'.$path)->status(), [404, 405]);
        }
        $this->get('/')->assertRedirect(route('clients.index'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('employees.index'));
    }
}
