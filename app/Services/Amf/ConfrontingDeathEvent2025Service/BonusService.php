<?php

namespace App\Services\Amf\ConfrontingDeathEvent2025Service;

use App\Models\Character;
use App\Models\CharacterConfrontingDeath;
use App\Helpers\ItemHelper;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function claimBonusRewards($charId, $sessionKey, $rewardIndex)
    {
        return DB::transaction(function () use ($charId, $rewardIndex) {
            $confrontingDeath = CharacterConfrontingDeath::lockForUpdate()->where('character_id', $charId)->first();
            
            if (!$confrontingDeath) return (object)['status' => 0, 'error' => 'Data not found'];

            $claimed = $confrontingDeath->claimed_milestones ?? [];
            if (in_array($rewardIndex, $claimed)) {
                return (object)['status' => 2, 'result' => 'Already claimed'];
            }
            
            if (!isset(RewardHelper::$milestoneData[$rewardIndex])) {
                 return (object)['status' => 0, 'error' => 'Invalid reward index'];
            }

            $milestone = RewardHelper::$milestoneData[$rewardIndex];
            
            if ($confrontingDeath->battles_won < $milestone['requirement']) {
                return (object)['status' => 2, 'result' => 'Requirement not met'];
            }
            
            $claimed[] = $rewardIndex;
            $confrontingDeath->claimed_milestones = $claimed;
            $confrontingDeath->save();
            
            // Give reward logic
            $rewardId = str_replace('%s', Character::find($charId)->gender, $milestone['id']);
            $quantity = $milestone['quantity'];
            
            ItemHelper::addItem($charId, $rewardId, $quantity);

            return (object)[
                'status' => 1,
                'reward' => $rewardId
            ];
        });
    }
}
