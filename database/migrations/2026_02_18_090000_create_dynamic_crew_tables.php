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
        // Dynamic Season configuration
        Schema::create('crew_seasons', function (Blueprint $table) {
            $table->id();
            $table->integer('season_identifier')->default(1);
            $table->timestamp('phase1_start_at')->nullable();
            $table->timestamp('phase1_end_at')->nullable(); // Start of Phase 2
            $table->timestamp('phase2_end_at')->nullable(); // End of Season
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Rewards for each season phase
        Schema::create('crew_season_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crew_season_id')->constrained()->onDelete('cascade');
            $table->integer('phase'); // 1 or 2
            $table->string('reward_type')->default('item'); // item, gold, token
            $table->string('reward_id'); // item_id
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // General dynamic settings for Crew (costs, limits, pool size)
        Schema::create('crew_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Minigame rewards
        Schema::create('crew_minigame_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('item_id');
            $table->string('category')->default('material');
            $table->integer('quantity')->default(1);
            $table->integer('probability')->default(100); // Weight or percentage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_minigame_rewards');
        Schema::dropIfExists('crew_settings');
        Schema::dropIfExists('crew_season_rewards');
        Schema::dropIfExists('crew_seasons');
    }
};
