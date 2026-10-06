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
        Schema::create('thickness_surcharges', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_thickness'); // 0, 18, 24, 30 (mm)
            $table->integer('surcharge_amount'); // 0, 10, 20, 40
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thickness_surcharges');
    }
};
