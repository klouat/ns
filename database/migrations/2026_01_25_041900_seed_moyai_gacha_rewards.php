<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clear existing data
        DB::table('moyai_gacha_rewards')->truncate();
        DB::table('moyai_gacha_bonuses')->truncate();

        // Load library.json and listskill.json
        $libraryPath = storage_path('app/library.json');
        $skillsPath = storage_path('app/listskill.json');
        
        $library = json_decode(file_get_contents($libraryPath), true);
        $skills = json_decode(file_get_contents($skillsPath), true);

        // === TOP TIER REWARDS (Rare - Weight 1) === ONLY 2 ITEMS!
        $topRewards = [];
        
        // Premium skills (price_tokens > 0) - Only 1 skill
        foreach ($skills as $skill) {
            if (isset($skill['premium']) && $skill['premium'] === true && isset($skill['price_tokens']) && $skill['price_tokens'] > 0) {
                $topRewards[] = [
                    'reward_id' => $skill['id'],
                    'tier' => 'top',
                    'weight' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                if (count($topRewards) >= 1) break; // Only 1 top tier skill
            }
        }
        
        // Premium weapons/items - Only 1 item
        foreach ($library as $item) {
            if (isset($item['premium']) && $item['premium'] === true && 
                isset($item['type']) && in_array($item['type'], ['wpn', 'set', 'back']) &&
                isset($item['price_tokens']) && $item['price_tokens'] >= 100) {
                $topRewards[] = [
                    'reward_id' => $item['id'],
                    'tier' => 'top',
                    'weight' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                if (count($topRewards) >= 2) break; // Total 2 top tier items (1 skill + 1 item)
            }
        }

        // === MID TIER REWARDS (Uncommon - Weight 5) ===
        $midRewards = [];
        
        // Mid-level skills (level 10-30, not premium)
        foreach ($skills as $skill) {
            if (isset($skill['level']) && $skill['level'] >= 10 && $skill['level'] <= 30 &&
                (!isset($skill['premium']) || $skill['premium'] === false) &&
                isset($skill['price_gold']) && $skill['price_gold'] > 0) {
                $midRewards[] = [
                    'reward_id' => $skill['id'],
                    'tier' => 'mid',
                    'weight' => 5,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                if (count($midRewards) >= 15) break;
            }
        }
        
        // Mid-level equipment
        foreach ($library as $item) {
            if (isset($item['level']) && $item['level'] >= 20 && $item['level'] <= 40 &&
                isset($item['type']) && in_array($item['type'], ['wpn', 'set', 'back', 'clothing']) &&
                (!isset($item['premium']) || $item['premium'] === false)) {
                $midRewards[] = [
                    'reward_id' => $item['id'],
                    'tier' => 'mid',
                    'weight' => 5,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                if (count($midRewards) >= 30) break;
            }
        }

        // === COMMON TIER REWARDS (Common - Weight 15-30) ===
        $commonRewards = [];
        
        // Materials and consumables
        $commonRewards[] = ['reward_id' => 'material_1:5', 'tier' => 'common', 'weight' => 30, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'material_2:3', 'tier' => 'common', 'weight' => 25, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'material_3:2', 'tier' => 'common', 'weight' => 20, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'gold_10000', 'tier' => 'common', 'weight' => 25, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'gold_25000', 'tier' => 'common', 'weight' => 20, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'gold_50000', 'tier' => 'common', 'weight' => 15, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'tokens_10', 'tier' => 'common', 'weight' => 10, 'created_at' => now(), 'updated_at' => now()];
        $commonRewards[] = ['reward_id' => 'tokens_25', 'tier' => 'common', 'weight' => 8, 'created_at' => now(), 'updated_at' => now()];
        
        // Low level skills
        foreach ($skills as $skill) {
            if (isset($skill['level']) && $skill['level'] >= 1 && $skill['level'] <= 5 &&
                isset($skill['price_gold']) && $skill['price_gold'] > 0 && $skill['price_gold'] <= 500) {
                $commonRewards[] = [
                    'reward_id' => $skill['id'],
                    'tier' => 'common',
                    'weight' => 15,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                if (count($commonRewards) >= 20) break;
            }
        }

        // Insert all rewards
        DB::table('moyai_gacha_rewards')->insert(array_merge($topRewards, $midRewards, $commonRewards));

        // === BONUS MILESTONES ===
        $bonuses = [
            ['requirement' => 10, 'reward_id' => 'gold_50000', 'quantity' => 1, 'sort_order' => 0],
            ['requirement' => 25, 'reward_id' => 'tokens_50', 'quantity' => 1, 'sort_order' => 1],
            ['requirement' => 50, 'reward_id' => 'gold_100000', 'quantity' => 1, 'sort_order' => 2],
            ['requirement' => 100, 'reward_id' => 'tokens_100', 'quantity' => 1, 'sort_order' => 3],
            ['requirement' => 200, 'reward_id' => 'gold_250000', 'quantity' => 1, 'sort_order' => 4],
            ['requirement' => 300, 'reward_id' => 'tokens_200', 'quantity' => 1, 'sort_order' => 5],
            ['requirement' => 500, 'reward_id' => 'gold_500000', 'quantity' => 1, 'sort_order' => 6],
            ['requirement' => 1000, 'reward_id' => 'tokens_500', 'quantity' => 1, 'sort_order' => 7],
        ];

        foreach ($bonuses as &$bonus) {
            $bonus['created_at'] = now();
            $bonus['updated_at'] = now();
        }

        DB::table('moyai_gacha_bonuses')->insert($bonuses);
    }

    public function down(): void
    {
        DB::table('moyai_gacha_rewards')->truncate();
        DB::table('moyai_gacha_bonuses')->truncate();
    }
};
