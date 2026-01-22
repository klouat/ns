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
        Schema::create('character_gear_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('New Preset');
            $table->string('weapon')->nullable();
            $table->string('clothing')->nullable();
            $table->string('hair')->nullable();
            $table->string('back_item')->nullable();
            $table->string('accessory')->nullable();
            $table->string('hair_color')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_gear_presets');
    }
};
