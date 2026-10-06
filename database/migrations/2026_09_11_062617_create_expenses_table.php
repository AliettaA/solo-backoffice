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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('paid_at');
            $table->foreignId('expense_category_id')->constrained();
            $table->foreignId('payee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payee_name_free')->nullable(); // マスタ未登録の支払先を直接入力する場合
            $table->unsignedInteger('amount');
            $table->enum('payment_method', ['現金', 'クレジット', '振込', 'その他'])->default('現金');
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
