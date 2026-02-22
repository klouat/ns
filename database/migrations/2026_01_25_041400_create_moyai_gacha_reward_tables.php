<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Moyai Gacha reward pool table
        Schema::create('moyai_gacha_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('reward_id'); // e.g., 'skill_1234', 'wpn_5678'
            $table->enum('tier', ['top', 'mid', 'common']); // Reward tier
            $table->integer('weight')->default(1); // Drop weight (higher = more common)
            $table->timestamps();
        });

        // Moyai Gacha bonus milestones table
        Schema::create('moyai_gacha_bonuses', function (Blueprint $table) {
            $table->id();
            $table->integer('requirement'); // Number of spins required
            $table->string('reward_id'); // Reward item ID
            $table->integer('quantity')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed some example data
        DB::table('moyai_gacha_bonuses')->insert([
            ['requirement' => 10, 'reward_id' => 'material_1:10', 'quantity' => 1, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 25, 'reward_id' => 'material_2:5', 'quantity' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 50, 'reward_id' => 'gold_100000', 'quantity' => 1, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 100, 'reward_id' => 'tokens_50', 'quantity' => 1, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 200, 'reward_id' => 'material_3:20', 'quantity' => 1, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 300, 'reward_id' => 'material_4:15', 'quantity' => 1, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 500, 'reward_id' => 'gold_500000', 'quantity' => 1, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['requirement' => 1000, 'reward_id' => 'tokens_200', 'quantity' => 1, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed some example gacha rewards
        DB::table('moyai_gacha_rewards')->insert([
            // Top tier (rare)
            ['reward_id' => 'skill_rare1', 'tier' => 'top', 'weight' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'skill_rare2', 'tier' => 'top', 'weight' => 1, 'created_at' => now(), 'updated_at' => now()],
            
            // Mid tier (uncommon)
            ['reward_id' => 'wpn_uncommon1', 'tier' => 'mid', 'weight' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'set_uncommon1', 'tier' => 'mid', 'weight' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'back_uncommon1', 'tier' => 'mid', 'weight' => 5, 'created_at' => now(), 'updated_at' => now()],
            
            // Common tier
            ['reward_id' => 'material_1:5', 'tier' => 'common', 'weight' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'material_2:3', 'tier' => 'common', 'weight' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'gold_10000', 'tier' => 'common', 'weight' => 15, 'created_at' => now(), 'updated_at' => now()],
            ['reward_id' => 'material_3:2', 'tier' => 'common', 'weight' => 15, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('moyai_gacha_bonuses');
        Schema::dropIfExists('moyai_gacha_rewards');
    }
};
