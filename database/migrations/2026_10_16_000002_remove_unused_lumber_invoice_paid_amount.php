<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lumber_invoices', 'paid_amount')) {
            return;
        }
        if (DB::table('lumber_invoices')->where('paid_amount', '<>', 0)->exists()) {
            throw new RuntimeException('納品書の入金済額に0以外の値があるため、削除を中止しました。');
        }
        Schema::table('lumber_invoices', fn (Blueprint $table) => $table->dropColumn('paid_amount'));
    }

    public function down(): void
    {
        if (! Schema::hasColumn('lumber_invoices', 'paid_amount')) {
            Schema::table('lumber_invoices', fn (Blueprint $table) => $table->unsignedBigInteger('paid_amount')->default(0)->after('total'));
        }
    }
};
