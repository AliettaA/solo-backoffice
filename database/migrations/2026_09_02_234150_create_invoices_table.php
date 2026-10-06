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
    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_number')->unique(); // INV-2026-0001
        $table->foreignId('client_id')->constrained()->cascadeOnDelete();
        $table->foreignId('estimate_id')->nullable()->constrained()->nullOnDelete();
        $table->string('title')->nullable();
        $table->date('issue_date'); // 取引年月日
        $table->date('due_date'); // 支払期限
        $table->enum('status', ['draft', 'sent', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('draft');
        $table->text('notes')->nullable();

        // インボイス要件: 税率ごとの合計・消費税額を分けて保持
        $table->unsignedBigInteger('subtotal_10')->default(0); // 10%対象小計
        $table->unsignedBigInteger('tax_10')->default(0);
        $table->unsignedBigInteger('subtotal_8')->default(0);  // 8%軽減対象小計
        $table->unsignedBigInteger('tax_8')->default(0);
        $table->unsignedBigInteger('total')->default(0);
        $table->unsignedBigInteger('paid_amount')->default(0);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
