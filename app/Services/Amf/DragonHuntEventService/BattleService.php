<?php

namespace App\Services\Amf\DragonHuntEventService;

use App\Models\Character;
use App\Models\User;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BattleService
{
    public function startBattle($charId, $bossId, $mode, $agility, $enemyStats, $hash, $sessionKey)
    {
        return DB::transaction(function () use ($charId, $bossId, $mode, $agility, $enemyStats, $hash, $sessionKey) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            // Validate hash: sha256(char_id + boss_id + mode + enemyStats + agility)
            $expectedHash = hash('sha256', $charId . $bossId . $mode . $enemyStats . $agility);
            if ($hash !== $expectedHash) {
                return (object)['status' => 2, 'result' => 'Invalid hash'];
            }

            if ($mode == 1) {
                if ($char->gold < 250000) {
                    return (object)['status' => 2, 'result' => 'Not enough gold'];
                }
                $char->gold -= 250000;
                $char->save();
            } elseif ($mode == 2) {
                if ($user->tokens < 100) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }
                $user->tokens -= 100;
                $user->save();
            }

            // Generate battle code
            $battleCode = Str::random(32);
            
            $captureRanges = [
                0 => [0, 5],      // Hard: 0-5%
                1 => [0, 15],     // Normal: 0-15%
                2 => [0, 25]      // Easy: 0-25%
            ];
            
            $range = $captureRanges[$mode] ?? [0, 5];
            $n1 = $range[0];
            $n2 = $range[1];

            $serverHash = hash('sha256', $bossId . $battleCode . $charId . $n1 . $n2);

            return (object)[
                'status' => 1,
                'code' => $battleCode,
                'hash' => $serverHash,
                'n1' => $n1,
                'n2' => $n2
            ];
        });
    }

    public function finishBattle($charId, $bossId, $captured = 0)
    {
        return DB::transaction(function () use ($charId, $bossId, $captured) {
            $char = Character::lockForUpdate()->find($charId);
            
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            $level = $char->level;
            $xpReward = floor($level * 2500 / 60);
            $goldReward = floor($level * 2500 / 60);

            $char->gold += $goldReward;
            $char->xp += $xpReward;

            $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
            $char->save();

            if ($char->equipped_pet_id) {
                ExperienceHelper::addEquippedPetXp($charId, floor($xpReward * 0.20));
            }

            return (object)[
                'status' => 1,
                'xp' => $char->xp,
                'level' => $char->level,
                'level_up' => $levelUp,
                'result' => [$goldReward, $xpReward, []]
            ];
        });
    }
}
