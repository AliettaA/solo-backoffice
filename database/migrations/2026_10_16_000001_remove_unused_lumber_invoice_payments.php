<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lumber_invoice_payments')) {
            return;
        }
        if (DB::table('lumber_invoice_payments')->exists()
            || DB::table('lumber_invoices')->where('paid_amount', '<>', 0)->exists()) {
            throw new RuntimeException('納品書別の入金記録または入金済額があるため、削除を中止しました。');
        }
        Schema::drop('lumber_invoice_payments');
    }

    public function down(): void
    {
        if (! Schema::hasTable('lumber_invoice_payments')) {
            (require __DIR__.'/2026_09_07_074828_create_lumber_invoice_payments_table.php')->up();
        }
    }
};
