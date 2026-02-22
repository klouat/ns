<?php

namespace App\Services\Amf\ConfrontingDeathEvent2025Service;

use App\Models\CharacterConfrontingDeath;

class DataService
{
    public function getBattleData($charId, $sessionKey)
    {
        $confrontingDeath = CharacterConfrontingDeath::firstOrCreate(
            ['character_id' => $charId],
            ['energy' => 8, 'battles_won' => 0, 'claimed_milestones' => []]
        );

        return (object)[
            'status' => 1,
            'energy' => $confrontingDeath->energy
        ];
    }

    public function getBonusRewards($charId, $sessionKey)
    {
        $confrontingDeath = CharacterConfrontingDeath::firstOrCreate(['character_id' => $charId]);
        
        $rewardsStatus = [];
        $claimed = $confrontingDeath->claimed_milestones ?? [];

        foreach (RewardHelper::$milestoneData as $index => $milestone) {
            $isClaimed = in_array($index, $claimed);
            $rewardsStatus[] = $isClaimed;
        }

        return (object)[
            'status' => 1,
            'milestone' => $confrontingDeath->battles_won,
            'rewards' => $rewardsStatus
        ];
    }
}
