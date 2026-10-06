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
        Schema::create('quantity_tier_surcharges', function (Blueprint $table) {
            $table->id();
            $table->string('label'); // "1-9", "10-14", "15-19", "20-"
            $table->unsignedInteger('min_qty');
            $table->unsignedInteger('max_qty')->nullable(); // nullは上限なし
            $table->integer('surcharge_amount');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quantity_tier_surcharges');
    }
};
