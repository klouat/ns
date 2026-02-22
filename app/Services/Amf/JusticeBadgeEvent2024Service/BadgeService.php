<?php

namespace App\Services\Amf\JusticeBadgeEvent2024Service;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BadgeService
{
    private const MATERIAL_BADGE = 'material_2110';

    public function getEventData($charId, $sessionKey)
    {
        $char = Character::find($charId);
        
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $badgeItem = CharacterItem::where('character_id', $charId)
            ->where('item_id', self::MATERIAL_BADGE)
            ->first();
        
        $ownedMaterials = $badgeItem ? $badgeItem->quantity : 0;
        $eventEnd = "Event ends: TBA";

        return (object)[
            'status' => 1,
            'materials' => $ownedMaterials,
            'end' => $eventEnd
        ];
    }

    public function exchange($charId, $sessionKey, $requirement)
    {
        return DB::transaction(function () use ($charId, $requirement) {
            $char = Character::lockForUpdate()->find($charId);
            
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            $badgeItem = CharacterItem::where('character_id', $charId)
                ->where('item_id', self::MATERIAL_BADGE)
                ->lockForUpdate()
                ->first();
            
            $ownedMaterials = $badgeItem ? $badgeItem->quantity : 0;

            if ($ownedMaterials < $requirement) {
                return (object)['status' => 2, 'result' => 'Not enough Justice Badges'];
            }

            $badgeItem->quantity -= $requirement;
            $badgeItem->save();

            $rewards = $this->getRewardsByRequirement($requirement, $char->level);
            $rewardsList = [];
            
            if (isset($rewards['xp'])) {
                $char->xp += $rewards['xp'];
                $rewardsList[] = ['type' => 'xp', 'amount' => $rewards['xp']];
            }
            
            if (isset($rewards['gold'])) {
                $char->gold += $rewards['gold'];
                $rewardsList[] = ['type' => 'gold', 'amount' => $rewards['gold']];
            }
            
            if (isset($rewards['tp'])) {
                $char->tp += $rewards['tp'];
                $rewardsList[] = ['type' => 'tp', 'amount' => $rewards['tp']];
            }
            
            if (isset($rewards['ss'])) {
                $char->character_ss += $rewards['ss'];
                $rewardsList[] = ['type' => 'ss', 'amount' => $rewards['ss']];
            }

            $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
            $char->save();

            if ($char->equipped_pet_id && isset($rewards['xp'])) {
                ExperienceHelper::addEquippedPetXp($charId, floor($rewards['xp'] * 0.20));
            }

            return (object)[
                'status' => 1,
                'materials' => $badgeItem->quantity,
                'rewards' => array_map(function($r) { return (object)$r; }, $rewardsList),
                'level' => $char->level,
                'xp' => $char->xp,
                'level_up' => $levelUp
            ];
        });
    }

    private function getRewardsByRequirement($requirement, $charLevel)
    {
        $xpForLevel = $this->calculateXpForLevel($charLevel);
        
        $rewardTiers = [
            5 => [   // 5 badges
                'xp' => floor($xpForLevel * 50 / 100),
                'gold' => 0,
                'tp' => 0,
                'ss' => 0
            ],
            10 => [  // 10 badges
                'xp' => 0,
                'gold' => 500000,
                'tp' => 0,
                'ss' => 0
            ],
            15 => [  // 15 badges
                'xp' => 0,
                'gold' => 0,
                'tp' => 50,
                'ss' => 0
            ],
            20 => [  // 20 badges
                'xp' => 0,
                'gold' => 0,
                'tp' => 0,
                'ss' => 10
            ]
        ];

        return $rewardTiers[$requirement] ?? [
            'xp' => 0,
            'gold' => 0,
            'tp' => 0,
            'ss' => 0
        ];
    }

    private function calculateXpForLevel($level)
    {
        return ExperienceHelper::getRequiredCharacterXp($level);
    }
}
