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
        Schema::create('character_monster_hunters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->integer('energy')->default(100);
            $table->date('last_energy_reset')->nullable();
            $table->string('boss_id')->default('boss_1'); // Default boss
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_monster_hunters');
    }
};
