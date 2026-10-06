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
    Schema::create('estimates', function (Blueprint $table) {
        $table->id();
        $table->string('estimate_number')->unique(); // EST-2026-0001 など
        $table->foreignId('client_id')->constrained()->cascadeOnDelete();
        $table->string('title')->nullable(); // 件名
        $table->date('issue_date');
        $table->date('expiry_date')->nullable(); // 有効期限
        $table->enum('status', ['draft', 'sent', 'accepted', 'declined', 'converted'])->default('draft');
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
        Schema::dropIfExists('estimates');
    }
};
