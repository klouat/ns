<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JusticeBadgeEvent2024Service
{
    private const MATERIAL_BADGE = 'material_2110';

    /**
     * Get event data (owned materials and event end date)
     * 
     * AMF Call: JusticeBadgeEvent2024.getEventData
     * Parameters: charId, sessionKey
     */
    public function getEventData($charId, $sessionKey)
    {
        $char = Character::find($charId);
        
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        // Get owned Justice Badge materials
        $badgeItem = CharacterItem::where('character_id', $charId)
            ->where('item_id', self::MATERIAL_BADGE)
            ->first();
        
        $ownedMaterials = $badgeItem ? $badgeItem->quantity : 0;

        // Event end date (you can configure this)
        $eventEnd = "Event ends: TBA"; // Or use a specific date from config

        return (object)[
            'status' => 1,
            'materials' => $ownedMaterials,
            'end' => $eventEnd
        ];
    }

    /**
     * Exchange Justice Badges for rewards
     * 
     * AMF Call: JusticeBadgeEvent2024.exchange
     * Parameters: charId, sessionKey, requirement
     * 
     * Rewards from gamedata.json:
     * - Index 0: XP (percentage based on level)
     * - Index 1: Gold (fixed amount)
     * - Index 2: TP (Training Points)
     * - Index 3: SS (Senjutsu Scrolls)
     */
    public function exchange($charId, $sessionKey, $requirement)
    {
        return DB::transaction(function () use ($charId, $requirement) {
            $char = Character::lockForUpdate()->find($charId);
            
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            // Get owned Justice Badge materials
            $badgeItem = CharacterItem::where('character_id', $charId)
                ->where('item_id', self::MATERIAL_BADGE)
                ->lockForUpdate()
                ->first();
            
            $ownedMaterials = $badgeItem ? $badgeItem->quantity : 0;

            // Check if player has enough materials
            if ($ownedMaterials < $requirement) {
                return (object)['status' => 2, 'result' => 'Not enough Justice Badges'];
            }

            // Deduct materials
            $badgeItem->quantity -= $requirement;
            $badgeItem->save();

            // Determine rewards based on requirement
            // These values should match gamedata.json structure
            $rewards = $this->getRewardsByRequirement($requirement, $char->level);

            // Apply rewards
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

            // Check for level up using ExperienceHelper
            $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
            $char->save();

            // Pet XP if applicable (20% of character XP gained)
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

    /**
     * Get rewards based on requirement amount
     * 
     * @param int $requirement
     * @param int $charLevel
     * @return array
     */
    private function getRewardsByRequirement($requirement, $charLevel)
    {
        // Calculate XP based on character level
        // Formula from AS: xpTable[level] * percentage / 100
        $xpForLevel = $this->calculateXpForLevel($charLevel);
        
        // Reward tiers based on requirement
        // These should match your gamedata.json structure
        $rewardTiers = [
            5 => [   // 5 badges
                'xp' => floor($xpForLevel * 50 / 100),  // 50% of level XP
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

    /**
     * Calculate XP required for a given level
     * Simplified version - should match ExperienceHelper table
     * 
     * @param int $level
     * @return int
     */
    private function calculateXpForLevel($level)
    {
        return ExperienceHelper::getRequiredCharacterXp($level);
    }
}
