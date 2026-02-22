<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrewDynamicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Season
        $seasonId = DB::table('crew_seasons')->insertGetId([
            'season_identifier' => 1,
            'phase1_start_at' => now(),
            'phase1_end_at' => now()->addMonth(), // Phase 1 ends in 1 month
            'phase2_end_at' => now()->addMonths(2), // Phase 2 ends in 2 months
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed Season Rewards
        // Phase 1: wpn_2021
        DB::table('crew_season_rewards')->insert([
            'crew_season_id' => $seasonId,
            'phase' => 1,
            'reward_type' => 'item',
            'reward_id' => 'wpn_2021',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Phase 2: Various skills and items
        $p2Rewards = ['skill_13', 'skill_14', 'skill_15', 'wpn_2024', 'wpn_2025', 'back_2024', 'back_2025'];
        foreach ($p2Rewards as $r) {
            DB::table('crew_season_rewards')->insert([
                'crew_season_id' => $seasonId,
                'phase' => 2,
                'reward_type' => 'item',
                'reward_id' => $r,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Seed Settings
        $settings = [
            ['key' => 'token_pool', 'value' => '100000', 'description' => 'Current token pool amount'],
            ['key' => 'token_pool_base', 'value' => '50000', 'description' => 'Base token pool amount'],
            ['key' => 'cost_create_crew', 'value' => '1000', 'description' => 'Token cost to create a crew'],
            ['key' => 'cost_rename_crew', 'value' => '3000', 'description' => 'Token cost to rename a crew'],
            ['key' => 'cost_increase_member_base', 'value' => '10', 'description' => 'Base multiplier cost to increase members'],
            ['key' => 'cost_stamina_restore_token', 'value' => '10', 'description' => 'Token cost to restore 50 stamina'],
        ];
        DB::table('crew_settings')->insert($settings);

        // 4. Seed Minigame Rewards
        DB::table('crew_minigame_rewards')->insert([
            [
                'item_id' => 'material_939',
                'quantity' => 1,
                'probability' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 'material_941',
                'quantity' => 1,
                'probability' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
