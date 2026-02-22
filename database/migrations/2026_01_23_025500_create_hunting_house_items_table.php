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
        Schema::create('hunting_house_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->index();
            $table->string('category');
            $table->json('materials');
            $table->json('quantities');
            $table->integer('sort_order')->default(0);
            $table->string('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hunting_house_items');
    }
};
