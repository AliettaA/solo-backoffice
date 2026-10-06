<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('salary_payments', fn (Blueprint $table) => $table->unsignedInteger('resident_tax')->default(0));
    }
    public function down(): void
    {
        Schema::table('salary_payments', fn (Blueprint $table) => $table->dropColumn('resident_tax'));
    }
};
