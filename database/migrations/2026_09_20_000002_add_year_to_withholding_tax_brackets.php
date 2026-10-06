<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withholding_tax_brackets', function (Blueprint $table) {
            $table->unsignedSmallInteger('tax_year')->default(2026)->index();
        });
    }

    public function down(): void
    {
        DB::table('withholding_tax_brackets')->where('tax_year', '!=', 2026)->delete();
        Schema::table('withholding_tax_brackets', function (Blueprint $table) {
            $table->dropIndex(['tax_year']);
            $table->dropColumn('tax_year');
        });
    }
};
