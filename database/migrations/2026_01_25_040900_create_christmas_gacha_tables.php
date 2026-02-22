<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Moyai Gacha (Daily Gacha) table
        Schema::create('character_moyai_gacha', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id')->unique();
            $table->integer('total_spins')->default(0);
            $table->json('claimed_bonuses')->nullable(); // Array of claimed bonus indices
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });

        // Gacha history table
        Schema::create('moyai_gacha_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('character_name');
            $table->integer('character_level');
            $table->string('reward_id');
            $table->integer('spin_count'); // How many spins (1, 3, or 6)
            $table->tinyInteger('currency'); // 0 = coins, 1 = tokens
            $table->timestamp('obtained_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->index(['obtained_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moyai_gacha_history');
        Schema::dropIfExists('character_moyai_gacha');
    }
};
