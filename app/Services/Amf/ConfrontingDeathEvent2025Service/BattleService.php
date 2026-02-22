<?php

namespace App\Services\Amf\ConfrontingDeathEvent2025Service;

use App\Models\Character;
use App\Models\CharacterConfrontingDeath;
use App\Models\User;
use App\Helpers\ExperienceHelper;
use App\Helpers\ItemHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BattleService
{
    public function refillEnergy($charId, $sessionKey)
    {
        return DB::transaction(function () use ($charId) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            $confrontingDeath = CharacterConfrontingDeath::lockForUpdate()->where('character_id', $charId)->first();

            $cost = 50;

            if ($user->tokens < $cost) {
                return (object)['status' => 2, 'result' => 'Not enough tokens'];
            }

            $user->tokens -= $cost;
            $user->save();

            $confrontingDeath->energy = 8;
            $confrontingDeath->save();

            return (object)[
                'status' => 1,
                'energy' => $confrontingDeath->energy
            ];
        });
    }

    public function startBattle($charId, $bossId, $agility, $enemyStats, $hash, $sessionKey)
    {
        $confrontingDeath = CharacterConfrontingDeath::where('character_id', $charId)->first();

        if ($confrontingDeath->energy < 1) {
            return (object)['status' => 2, 'result' => 'Not enough energy'];
        }
        
        $confrontingDeath->energy -= 1;
        $confrontingDeath->save();

        $code = Str::random(32);

        return (object)[
            'status' => 1,
            'code' => $code
        ];
    }

    public function finishBattle($charId, $bossId, $code, $damage, $hash, $result, $sessionKey)
    {
        $char = Character::lockForUpdate()->find($charId);
        $confrontingDeath = CharacterConfrontingDeath::lockForUpdate()->where('character_id', $charId)->first();
        
        $win = true; // Assume win if method is called

        $goldReward = 0;
        $xpReward = 0;
        $itemsRewarded = [];

        if ($win) {
            if ($confrontingDeath) {
                $confrontingDeath->battles_won += 1;
                $confrontingDeath->save();
            }

            // Calculate Reward
            $level = $char->level;
            $xpReward = floor($level * 2500 / 60);
            $goldReward = floor($level * 2500 / 60);
            
            // Add rewards to char
            $char->gold += $goldReward;
            $char->xp += $xpReward;
            
            // Level Up Check using ExperienceHelper
            $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
            $char->save();
            
            // Pet XP (20% of character XP)
            if ($char->equipped_pet_id) {
                ExperienceHelper::addEquippedPetXp($charId, floor($xpReward * 0.20));
            }
            
            if (!empty(RewardHelper::$bossData['rewards'])) {
                $rewardItem = RewardHelper::$bossData['rewards'][array_rand(RewardHelper::$bossData['rewards'])];
                ItemHelper::addItem($charId, $rewardItem, 1);
                $itemsRewarded[] = $rewardItem;
            }
        }

        return (object)[
            'status' => 1,
            'xp' => $char->xp,
            'level' => $char->level,
            'level_up' => $levelUp ?? false,
            'result' => [$goldReward, $xpReward, $itemsRewarded]
        ];
    }
}
