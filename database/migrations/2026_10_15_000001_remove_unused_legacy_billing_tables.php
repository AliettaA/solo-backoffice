<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check every table before any DDL: MySQL DDL cannot be rolled back.
        $tables = ['payments', 'invoice_items', 'invoices', 'estimate_items', 'estimates'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("旧テーブル {$table} にデータがあるため、削除を中止しました。");
            }
        }
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // Restore the empty schema only; up() never removes populated tables.
        foreach ([
            '2026_09_02_234148_create_estimates_table.php',
            '2026_09_02_234149_create_estimate_items_table.php',
            '2026_09_02_234150_create_invoices_table.php',
            '2026_09_02_234151_create_invoice_items_table.php',
            '2026_09_02_234152_create_payments_table.php',
        ] as $file) {
            (require __DIR__.'/'.$file)->up();
        }
    }
};
