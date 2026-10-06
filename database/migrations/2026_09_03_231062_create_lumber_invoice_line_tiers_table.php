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
    Schema::create('lumber_invoice_line_tiers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lumber_invoice_line_id')->constrained()->cascadeOnDelete();
        $table->string('tier_label'); // "1-9" など
        $table->unsignedInteger('unit_price'); // 自動計算された最終単価
        $table->unsignedInteger('quantity')->default(0);
        $table->unsignedInteger('amount')->default(0); // unit_price * quantity
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lumber_invoice_line_tiers');
    }
};
