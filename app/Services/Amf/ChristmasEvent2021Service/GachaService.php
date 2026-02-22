<?php

namespace App\Services\Amf\ChristmasEvent2021Service;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterMoyaiGacha;
use App\Models\MoyaiGachaHistory;
use App\Models\MoyaiGachaReward;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GachaService
{
    private const MATERIAL_GACHA = 'material_874';
    private const PRICE_TOKENS = [50, 100, 250];

    public function getGachaRewards($sessionKey, $charId, $playType, $playQty)
    {
        return DB::transaction(function () use ($charId, $playType, $playQty) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            
            if (!$char || !$user) {
                return (object)['status' => 0, 'error' => 'Character or user not found'];
            }

            // Determine cost and currency
            $currency = 0; // 0 = coins, 1 = tokens
            $cost = 0;

            if ($playType === 'coins') {
                $currency = 0;
                $cost = $playQty; // 1 or 3 coins
                
                // Check if player has enough coins
                $coinItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', self::MATERIAL_GACHA)
                    ->lockForUpdate()
                    ->first();
                
                $ownedCoins = $coinItem ? $coinItem->quantity : 0;
                
                if ($ownedCoins < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough gacha coins'];
                }
                
                // Deduct coins
                $coinItem->quantity -= $cost;
                $coinItem->save();
            } else {
                // tokens
                $currency = 1;
                // Map playQty to price index: 1 -> 50, 3 -> 100, 6 -> 250
                $priceIndex = $playQty == 1 ? 0 : ($playQty == 6 ? 2 : 1);
                $cost = self::PRICE_TOKENS[$priceIndex];
                
                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }
                
                // Deduct tokens
                $user->tokens -= $cost;
                $user->save();
            }

            // Generate rewards (automatically skips skills player already owns)
            $rewards = $this->generateGachaRewards($playQty, $charId);

            // Actually give rewards to the character
            foreach ($rewards as $reward) {
                RewardHelper::addRewardToCharacter($char, $reward);
            }

            // Update gacha record
            $gachaRecord = CharacterMoyaiGacha::firstOrCreate(
                ['character_id' => $charId],
                ['total_spins' => 0, 'claimed_bonuses' => []]
            );
            
            $gachaRecord->total_spins += $playQty;
            $gachaRecord->save();

            // Record history for each reward
            foreach ($rewards as $reward) {
                MoyaiGachaHistory::create([
                    'character_id' => $charId,
                    'character_name' => $char->name,
                    'character_level' => $char->level,
                    'reward_id' => $reward,
                    'spin_count' => $playQty,
                    'currency' => $currency,
                    'obtained_at' => Carbon::now()
                ]);
            }

            // Get updated coin count
            $coinItem = CharacterItem::where('character_id', $charId)
                ->where('item_id', self::MATERIAL_GACHA)
                ->first();
            
            $updatedCoins = $coinItem ? $coinItem->quantity : 0;

            return (object)[
                'status' => 1,
                'rewards' => $rewards,
                'coin' => $updatedCoins
            ];
        });
    }

    private function generateGachaRewards($quantity, $charId = null)
    {
        $rewards = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $maxAttempts = 10; // Prevent infinite loop
            $attempts = 0;
            $selectedReward = null;
            
            while ($attempts < $maxAttempts) {
                // Get all rewards with their weights
                $allRewards = MoyaiGachaReward::all();
                
                if ($allRewards->isEmpty()) {
                    // Fallback if no rewards in database
                    $selectedReward = 'material_1:1';
                    break;
                }
                
                // Calculate total weight
                $totalWeight = $allRewards->sum('weight');
                
                // Random weighted selection
                $random = rand(1, $totalWeight);
                $currentWeight = 0;
                
                foreach ($allRewards as $reward) {
                    $currentWeight += $reward->weight;
                    if ($random <= $currentWeight) {
                        $selectedReward = $reward->reward_id;
                        break;
                    }
                }
                
                // Check if it's a skill and if player already owns it
                if ($charId && strpos($selectedReward, 'skill_') === 0) {
                    // Check if player already has this skill
                    $hasSkill = \App\Models\CharacterSkill::where('character_id', $charId)
                        ->where('skill_id', $selectedReward)
                        ->exists();
                    
                    if ($hasSkill) {
                        // Player already has this skill, re-roll
                        $attempts++;
                        continue;
                    }
                }
                
                // Valid reward found
                break;
            }
            
            // If we couldn't find a unique skill after max attempts, give gold instead
            if ($attempts >= $maxAttempts && strpos($selectedReward, 'skill_') === 0) {
                $selectedReward = 'gold_25000';
            }
            
            $rewards[] = $selectedReward;
        }
        
        return $rewards;
    }
}
