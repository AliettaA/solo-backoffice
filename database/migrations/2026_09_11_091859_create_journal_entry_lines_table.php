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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['debit', 'credit']); // debit=借方(左), credit=貸方(右)
            $table->foreignId('expense_category_id')->constrained(); // 勘定科目(既存マスタを流用)
            $table->string('counterparty_name')->nullable(); // 取引先・お客様名
            $table->unsignedInteger('amount');
            $table->text('memo')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
