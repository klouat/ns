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
        Schema::create('material_market_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->index(); // The item to be crafted
            $table->string('category'); // wpn, back, set, etc.
            $table->json('materials'); // ["material_1", "wpn_old"]
            $table->json('quantities'); // [10, 1]
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_market_items');
    }
};
