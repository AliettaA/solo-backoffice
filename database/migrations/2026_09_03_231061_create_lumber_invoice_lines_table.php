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
    Schema::create('lumber_invoice_lines', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lumber_invoice_id')->constrained()->cascadeOnDelete();
        $table->foreignId('lumber_species_id')->constrained();
        $table->decimal('thickness', 6, 2); // 14, 18, 33 (mm)
        $table->decimal('width', 6, 2); // 12, 15, 10.5 (cm)
        $table->decimal('length', 6, 2)->default(4); // 4, 3, 2 (m)
        $table->integer('adjustment_amount')->default(0); // 返品/補填(直接金額、マイナス可)
        $table->unsignedInteger('total_quantity')->default(0);
        $table->unsignedBigInteger('line_total')->default(0);
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lumber_invoice_lines');
    }
};
