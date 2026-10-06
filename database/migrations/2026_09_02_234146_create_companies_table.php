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
    Schema::create('companies', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // 屋号または氏名
        $table->string('representative_name')->nullable(); // 代表者名
        $table->string('postal_code')->nullable();
        $table->string('address')->nullable();
        $table->string('phone')->nullable();
        $table->string('email')->nullable();
        $table->string('invoice_registration_number')->nullable(); // T+13桁
        $table->string('bank_name')->nullable();
        $table->string('bank_branch')->nullable();
        $table->string('bank_account_type')->nullable(); // 普通/当座
        $table->string('bank_account_number')->nullable();
        $table->string('bank_account_holder')->nullable();
        $table->string('seal_image_path')->nullable(); // 角印画像
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
