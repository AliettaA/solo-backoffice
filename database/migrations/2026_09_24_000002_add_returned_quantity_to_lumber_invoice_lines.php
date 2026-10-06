<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('lumber_invoice_lines', fn (Blueprint $table) => $table->unsignedInteger('returned_quantity')->default(0));
    }
    public function down(): void
    {
        Schema::table('lumber_invoice_lines', fn (Blueprint $table) => $table->dropColumn('returned_quantity'));
    }
};
