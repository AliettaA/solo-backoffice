<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lumber_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_amount')->default(0)->after('total');
        });

        // status に 'partially_paid' を追加(既存: draft, sent, paid)
        DB::statement("ALTER TABLE lumber_invoices MODIFY status ENUM('draft', 'sent', 'partially_paid', 'paid') DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('lumber_invoices', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });

        DB::statement("ALTER TABLE lumber_invoices MODIFY status ENUM('draft', 'sent', 'paid') DEFAULT 'draft'");
    }
};