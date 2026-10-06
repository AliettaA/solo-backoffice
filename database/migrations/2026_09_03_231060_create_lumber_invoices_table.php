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
    Schema::create('lumber_invoices', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_number')->unique();
        $table->foreignId('client_id')->constrained()->cascadeOnDelete();
        $table->date('issue_date'); // その日の日付
        $table->enum('status', ['draft', 'sent', 'paid'])->default('draft');
        $table->text('notes')->nullable();
        $table->unsignedBigInteger('subtotal')->default(0);
        $table->unsignedBigInteger('tax_amount')->default(0);
        $table->unsignedBigInteger('total')->default(0);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lumber_invoices');
    }
};
