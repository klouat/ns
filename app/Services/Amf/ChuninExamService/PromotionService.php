<?php

namespace App\Services\Amf\ChuninExamService;

use App\Models\Character;
use App\Models\CharacterChuninProgress;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * promoteToChunin
     * Parameters: [sessionKey, charId]
     */
    public function promoteToChunin($sessionKey, $charId)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $progress = CharacterChuninProgress::where('character_id', $charId)->first();

        // Check if all stages completed
        $MAX_STAGES = 5;

        // If current stage > MAX_STAGES, it means they finished them all
        if ($progress && $progress->current_stage > $MAX_STAGES) {
            
            if ($char->rank == 'Genin' || $char->rank == 'Chunin') {
                $char->rank = 'Tensai Chunin';
                $char->save();

                $genderSuffix = $char->gender == 1 ? '_1' : '_0';
                $setId = 'set_150' . $genderSuffix; // Chunin Vest
                $weaponId = 'wpn_794'; // Chunin Katana?
                $skillId = 'skill_109'; // Transformation?
                $tokenAmount = 200;

                // Rewards structure passed to client
                $rewards = [
                    'xp' => 10000,
                    'gold' => 50000,
                    'items' => [
                        ['id' => $setId, 'qty' => 1],
                        ['id' => $weaponId, 'qty' => 1],
                        ['id' => $skillId, 'qty' => 1] // Assuming skill is treated as item or handled separately by client
                        // If separate skill array needed: 'skills' => [$skillId]
                    ], 
                    'token' => $tokenAmount
                ];
                
                // --- Apply Rewards to DB ---
                
                // Character accumulated XP will now be processed by ExperienceHelper::checkCharacterLevelUp,
                // which now deducts XP as levels are gained.

                // Gold & XP
                $char->gold += $rewards['gold'];
                $char->xp += $rewards['xp'];
                \App\Helpers\ExperienceHelper::checkCharacterLevelUp($char);
                
                // Items
                $itemsToAdd = [$setId, $weaponId];
                foreach ($itemsToAdd as $itemId) {
                    // Check categories, assume 'set' and 'weapon' based on ID prefix or logic
                    // Using generic ItemHelper or DB calls
                    $item = \App\Models\CharacterItem::firstOrCreate(
                        ['character_id' => $charId, 'item_id' => $itemId],
                        ['quantity' => 0] 
                    );
                    $item->quantity += 1;
                    $item->save();
                }

                // Skill
                // Check if user already has skill
                $hasSkill = \App\Models\CharacterSkill::where('character_id', $charId)->where('skill_id', $skillId)->exists();
                if (!$hasSkill) {
                    \App\Models\CharacterSkill::create([
                        'character_id' => $charId,
                        'skill_id' => $skillId
                    ]);
                }

                // Tokens (Account level usually, stored on User)
                $user = $char->user;
                if ($user) {
                     $user->tokens += $tokenAmount;
                     $user->save();
                }

                $char->save();

                // Ensure character data is fresh
                $char->refresh();

                return (object)[
                    'status' => 1, 
                    'message' => 'Congratulations! You are now a Tensai Chunin!',
                    'rewards' => $rewards,
                    'char_data' => (object)[
                        'char_id' => $char->id,
                        'char_level' => $char->level,
                        'char_rank' => $char->rank, 
                        'char_gold' => $char->gold,
                        'char_xp' => $char->xp,
                    ]
                ];
            } else {
                 return (object)['status' => 1, 'message' => 'You are already a Tensai Chunin or higher!'];
            }
        }

        return (object)['status' => 0, 'error' => 'You must complete all exam stages first.'];
    }
}
