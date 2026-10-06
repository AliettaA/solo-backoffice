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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // 会社名/個人名
        $table->string('honorific')->default('御中'); // 御中 / 様
        $table->string('postal_code')->nullable();
        $table->string('address')->nullable();
        $table->string('contact_person')->nullable(); // 担当者名
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->text('memo')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
