<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyBillingRetirementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['clients', 'estimates', 'estimate_items', 'invoices', 'invoice_items', 'payments'] as $table) {
            $file = glob(database_path('migrations/*create_'.$table.'_table.php'))[0];
            (require $file)->up();
        }
    }

    public function test_empty_legacy_schema_is_removed_and_can_be_restored(): void
    {
        $migration = require database_path('migrations/2026_10_15_000001_remove_unused_legacy_billing_tables.php');
        DB::table('clients')->insert(['name' => '保持対象']);
        $before = DB::table('clients')->get()->toJson();
        $migration->up();
        foreach (['estimates', 'estimate_items', 'invoices', 'invoice_items', 'payments'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertSame($before, DB::table('clients')->get()->toJson());
        $migration->down();
        foreach (['estimates', 'estimate_items', 'invoices', 'invoice_items', 'payments'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    public function test_any_legacy_data_aborts_before_dropping_even_empty_tables(): void
    {
        $client = DB::table('clients')->insertGetId(['name' => '保持対象']);
        DB::table('estimates')->insert(['estimate_number' => 'TEST-1', 'client_id' => $client, 'issue_date' => '2025-01-01']);
        $migration = require database_path('migrations/2026_10_15_000001_remove_unused_legacy_billing_tables.php');
        try {
            $migration->up();
            $this->fail('Populated legacy tables must not be dropped.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('削除を中止', $e->getMessage());
        }
        foreach (['estimates', 'estimate_items', 'invoices', 'invoice_items', 'payments'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertSame(1, DB::table('estimates')->count());
    }
}
