<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lumber_invoice_lines', fn (Blueprint $table) => $table->integer('unit_price_adjustment')->default(0));
        Schema::table('lumber_invoices', function (Blueprint $table) {
            $table->integer('invoice_adjustment_amount')->default(0);
            $table->string('invoice_adjustment_description', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('lumber_invoice_lines', fn (Blueprint $table) => $table->dropColumn('unit_price_adjustment'));
        Schema::table('lumber_invoices', fn (Blueprint $table) => $table->dropColumn(['invoice_adjustment_amount', 'invoice_adjustment_description']));
    }
};
