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
        Schema::create('blacksmith_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->index();
            $table->json('materials');
            $table->json('quantities');
            $table->integer('gold_price');
            $table->integer('token_price');
            $table->string('req_weapon')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacksmith_items');
    }
};
