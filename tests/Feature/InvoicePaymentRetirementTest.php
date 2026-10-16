<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InvoicePaymentRetirementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['clients', 'lumber_invoices', 'lumber_invoice_payments', 'client_payments'] as $table) {
            (require glob(database_path('migrations/*create_'.$table.'_table.php'))[0])->up();
        }
        // The production addition also modifies a MySQL enum; add its column for SQLite.
        Schema::table('lumber_invoices', fn (Blueprint $table) => $table->unsignedBigInteger('paid_amount')->default(0));
        $client = DB::table('clients')->insertGetId(['name' => '検証用']);
        DB::table('lumber_invoices')->insert(['id' => 1, 'invoice_number' => 'TEST', 'client_id' => $client, 'issue_date' => '2025-01-01', 'total' => 1100]);
        DB::table('client_payments')->insert(['client_id' => $client, 'paid_at' => '2025-01-31', 'amount' => 1100]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_16_000001_remove_unused_lumber_invoice_payments.php');
    }

    public function test_empty_retirement_preserves_current_receipts_and_invoice_values(): void
    {
        $invoice = DB::table('lumber_invoices')->get()->toJson();
        $receipts = DB::table('client_payments')->get()->toJson();
        $migration = $this->migration();
        $migration->up();
        $migration->up();
        $this->assertFalse(Schema::hasTable('lumber_invoice_payments'));
        $this->assertSame($invoice, DB::table('lumber_invoices')->get()->toJson());
        $this->assertSame($receipts, DB::table('client_payments')->get()->toJson());
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('lumber-invoices.payments.store'));
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('lumber-invoices.payments.destroy'));
        $migration->down();
        $this->assertTrue(Schema::hasTable('lumber_invoice_payments'));
    }

    public function test_existing_invoice_payment_is_never_deleted(): void
    {
        DB::table('lumber_invoice_payments')->insert(['lumber_invoice_id' => 1, 'paid_at' => '2025-01-31', 'amount' => 100]);
        try {
            $this->migration()->up();
            $this->fail('Populated table must be preserved.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('削除を中止', $e->getMessage());
        }
        $this->assertSame(100, DB::table('lumber_invoice_payments')->value('amount'));
    }

    public function test_nonzero_invoice_paid_amount_also_blocks_retirement(): void
    {
        DB::table('lumber_invoices')->update(['paid_amount' => 100]);
        try {
            $this->migration()->up();
            $this->fail('Nonzero historical totals must block retirement.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('削除を中止', $e->getMessage());
        }
        $this->assertTrue(Schema::hasTable('lumber_invoice_payments'));
        $this->assertSame(100, DB::table('lumber_invoices')->value('paid_amount'));
    }
}
