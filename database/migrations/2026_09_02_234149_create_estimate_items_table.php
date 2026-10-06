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
    Schema::create('estimate_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
        $table->string('description');
        $table->decimal('quantity', 10, 2)->default(1);
        $table->string('unit')->default('式');
        $table->unsignedInteger('unit_price');
        $table->unsignedTinyInteger('tax_rate')->default(10);
        $table->unsignedInteger('amount'); // quantity * unit_price
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimate_items');
    }
};
