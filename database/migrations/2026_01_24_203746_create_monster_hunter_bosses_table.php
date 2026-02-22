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
        Schema::create('monster_hunter_bosses', function (Blueprint $table) {
            $table->id();
            $table->string('boss_id')->unique();
            $table->integer('xp')->default(0);
            $table->integer('gold')->default(0);
            $table->json('rewards')->nullable(); // Stores rewards as JSON array
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monster_hunter_bosses');
    }
};
