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
        Schema::create('withholding_tax_brackets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_amount');
            $table->unsignedInteger('max_amount');
            $table->unsignedInteger('dep_0');
            $table->unsignedInteger('dep_1');
            $table->unsignedInteger('dep_2');
            $table->unsignedInteger('dep_3');
            $table->unsignedInteger('dep_4');
            $table->unsignedInteger('dep_5');
            $table->unsignedInteger('dep_6');
            $table->unsignedInteger('dep_7');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_brackets');
    }
};
