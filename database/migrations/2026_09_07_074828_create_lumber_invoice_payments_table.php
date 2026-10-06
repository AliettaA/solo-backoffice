<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lumber_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lumber_invoice_id')->constrained()->cascadeOnDelete();
            $table->date('paid_at');
            $table->unsignedInteger('amount');
            $table->string('method')->nullable(); // 銀行振込 等
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lumber_invoice_payments');
    }
};