<?php

namespace App\Services\Amf\ChristmasEvent2021Service;

use App\Models\Character;
use App\Models\CharacterMoyaiGacha;
use App\Models\MoyaiGachaBonus;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function getBonusRewards($sessionKey, $charId, $accountId)
    {
        $gachaRecord = CharacterMoyaiGacha::where('character_id', $charId)->first();
        
        if (!$gachaRecord) {
            $gachaRecord = CharacterMoyaiGacha::create([
                'character_id' => $charId,
                'total_spins' => 0,
                'claimed_bonuses' => []
            ]);
        }

        // Bonus milestones (from gamedata.json or database)
        $bonusData = $this->getBonusMilestones();
        
        // Check which bonuses have been claimed
        $claimedBonuses = $gachaRecord->claimed_bonuses ?? [];
        $rewardsStatus = [];
        
        foreach ($bonusData as $index => $bonus) {
            $rewardsStatus[] = in_array($index, $claimedBonuses);
        }

        return (object)[
            'status' => 1,
            'total_spins' => $gachaRecord->total_spins,
            'data' => array_map(function($b) { return (object)$b; }, $bonusData),
            'rewards' => $rewardsStatus
        ];
    }

    public function claimBonusGachaRewards($sessionKey, $charId, $bonusIndex)
    {
        return DB::transaction(function () use ($charId, $bonusIndex) {
            $char = Character::lockForUpdate()->find($charId);
            $gachaRecord = CharacterMoyaiGacha::lockForUpdate()
                ->where('character_id', $charId)
                ->first();
            
            if (!$gachaRecord) {
                return (object)['status' => 0, 'error' => 'Gacha record not found'];
            }

            $bonusData = $this->getBonusMilestones();
            
            if (!isset($bonusData[$bonusIndex])) {
                return (object)['status' => 0, 'error' => 'Invalid bonus index'];
            }

            $bonus = $bonusData[$bonusIndex];
            $claimedBonuses = $gachaRecord->claimed_bonuses ?? [];

            // Check if already claimed
            if (in_array($bonusIndex, $claimedBonuses)) {
                return (object)['status' => 2, 'result' => 'Bonus already claimed'];
            }

            // Check if requirement met
            if ($gachaRecord->total_spins < $bonus['req']) {
                return (object)['status' => 2, 'result' => 'Requirement not met'];
            }

            // Mark as claimed
            $claimedBonuses[] = $bonusIndex;
            $gachaRecord->claimed_bonuses = $claimedBonuses;
            $gachaRecord->save();

            // Actually give the reward to the character
            $rewardId = $bonus['id'];
            RewardHelper::addRewardToCharacter($char, $rewardId);

            // Return reward (client expects this format)
            return (object)[
                'status' => 1,
                'reward' => [$rewardId]
            ];
        });
    }

    private function getBonusMilestones()
    {
        $bonuses = MoyaiGachaBonus::orderBy('sort_order')->get();
        
        return $bonuses->map(function ($bonus) {
            return [
                'id' => $bonus->reward_id,
                'req' => $bonus->requirement,
                'claimed' => false // Will be set by caller
            ];
        })->toArray();
    }
}
