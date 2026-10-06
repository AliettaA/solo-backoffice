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
        Schema::create('lumber_width_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lumber_species_id')->constrained()->cascadeOnDelete();
            $table->decimal('width', 6, 2); // 12, 15, 18 など(cm)
            $table->unsignedInteger('base_price');
            $table->timestamps();
            $table->unique(['lumber_species_id', 'width']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lumber_width_prices');
    }
};
