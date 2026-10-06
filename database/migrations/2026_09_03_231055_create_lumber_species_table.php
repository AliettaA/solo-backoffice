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
        Schema::create('lumber_species', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 杉, 桧, 杉役, 桧役, 加工直し
            $table->enum('pricing_type', ['by_width', 'flat']); // 幅で決まる か 固定か
            $table->unsignedInteger('flat_price')->nullable(); // flatの場合の単価
            $table->boolean('apply_thickness_surcharge')->default(true); // 厚み加算を適用するか
            $table->boolean('apply_length_adjustment')->default(true); // 長さ調整を適用するか
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lumber_species');
    }
};
