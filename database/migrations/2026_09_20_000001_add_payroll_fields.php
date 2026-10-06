<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employment_type')->default('employee');
        });
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->unsignedInteger('social_insurance')->default(0);
            $table->unsignedTinyInteger('dependents')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('employment_type'));
        Schema::table('salary_payments', fn (Blueprint $table) => $table->dropColumn(['social_insurance', 'dependents']));
    }
};
