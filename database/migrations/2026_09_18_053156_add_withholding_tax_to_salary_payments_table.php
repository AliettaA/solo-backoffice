<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->unsignedInteger('gross_amount')->default(0)->after('amount'); // 支給額(源泉徴収前)
            $table->unsignedInteger('withholding_tax')->default(0)->after('gross_amount'); // 源泉徴収税額
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'withholding_tax']);
        });
    }
};
