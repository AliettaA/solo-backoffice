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
    Schema::create('opening_balances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('expense_category_id')->constrained();
        $table->date('effective_date'); // この日付時点での残高(例: 2024-01-01)
        $table->integer('amount'); // マイナスもあり得るので符号付き
        $table->text('memo')->nullable();
        $table->timestamps();
        $table->unique(['expense_category_id', 'effective_date']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
