<?php

namespace App\Services\Amf\AdvanceAcademyService;

use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\User;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SkillService
{
    private $chains = [
        'wind' => [
            'evasion' => ["skill_39","skill_271","skill_272","skill_273","skill_274","skill_275"],
            'blade_of_wind' => ["skill_85","skill_276","skill_277","skill_278","skill_279"],
            'wind_peace' => ["skill_161","skill_280","skill_281","skill_282"],
            'dance_of_fujin' => ["skill_151","skill_283","skill_284"],
            'breakthrough' => ["skill_285","skill_286"],
        ],
        'fire' => [
            'fire_power' => ["skill_36","skill_220","skill_221","skill_222","skill_223","skill_224"],
            'hell_fire' => ["skill_86","skill_225","skill_226","skill_227","skill_228"],
            'fire_energy' => ["skill_162","skill_229","skill_230","skill_231"],
            'rage' => ["skill_152","skill_232","skill_233"],
            'phoenix' => ["skill_234","skill_235"],
        ],
        'thunder' => [
            'charge' => ["skill_35","skill_288","skill_289","skill_290","skill_291","skill_292"],
            'flash' => ["skill_87","skill_293","skill_294","skill_295","skill_296"],
            'bundle' => ["skill_163","skill_297","skill_298","skill_299"],
            'armor' => ["skill_153","skill_300","skill_301"],
            'boost' => ["skill_302","skill_303"],
        ],
        'earth' => [
            'golem' => ["skill_59","skill_237","skill_238","skill_239","skill_240","skill_241"],
            'absorb' => ["skill_88","skill_242","skill_243","skill_244","skill_245"],
            'rocks' => ["skill_164","skill_246","skill_247","skill_248"],
            'embrace' => ["skill_154","skill_249","skill_250"],
            'gaunt' => ["skill_251","skill_252"],
        ],
        'water' => [
            'renewal' => ["skill_60","skill_254","skill_255","skill_256","skill_257","skill_258"],
            'bundle' => ["skill_89","skill_259","skill_260","skill_261","skill_262"],
            'prison' => ["skill_165","skill_264","skill_263","skill_265"],
            'shield' => ["skill_155","skill_266","skill_267"],
            'shark' => ["skill_268","skill_269"],
        ],
        'genjutsu' => [
            'sealing' => ["skill_706","skill_726"]
        ]
    ];

    public function upgradeSkill($charId, $sessionKey, $skillId)
    {
        try {
            return DB::transaction(function () use ($charId, $skillId) {
                // 1. Get Character and User
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                // 2. Identify the chain and position
                $foundChain = null;
                $currentIndex = -1;

                foreach ($this->chains as $element => $groups) {
                    foreach ($groups as $groupName => $skills) {
                        $searchIndex = array_search($skillId, $skills);
                        if ($searchIndex !== false) {
                            $foundChain = $skills;
                            $currentIndex = $searchIndex;
                            break 2;
                        }
                    }
                }

                if ($foundChain === null) {
                    return (object)['status' => 2, 'result' => 'Skill not found in Academy Chains.'];
                }

                // 3. Logic check:
                // If currentIndex > 0, user must have the previous skill
                $prevSkillId = ($currentIndex > 0) ? $foundChain[$currentIndex - 1] : null;

                if ($prevSkillId) {
                    $hasPrev = CharacterSkill::where('character_id', $charId)
                        ->where('skill_id', $prevSkillId)
                        ->exists();
                    
                    if (!$hasPrev) {
                         return (object)['status' => 2, 'result' => 'You must learn the previous level first!'];
                    }
                } else {
                    // It's the first skill in chain. Check if they already have it?
                    // Or if they have any higher level?
                    // Ideally, just check if they already have *this* skill.
                    $hasThis = CharacterSkill::where('character_id', $charId)
                        ->where('skill_id', $skillId)
                        ->exists();
                    if ($hasThis) {
                         return (object)['status' => 2, 'result' => 'You already have this skill!'];
                    }
                }

                // 4. Get Skill Price
                $skillData = GameDataHelper::find_skill($skillId);
                if (!$skillData) {
                    return (object)['status' => 2, 'result' => 'Skill data not found!'];
                }

                $costGold = $skillData['price_gold'] ?? 0;
                $costTokens = $skillData['price_tokens'] ?? 0;

                // 5. Check Resources
                if ($user->tokens < $costTokens) {
                    return (object)['status' => 2, 'result' => 'Not enough Tokens!'];
                }
                if ($char->gold < $costGold) {
                    return (object)['status' => 2, 'result' => 'Not enough Gold!'];
                }

                // 6. Process Payment
                if ($costTokens > 0) {
                    $user->tokens -= $costTokens;
                    $user->save();
                }
                if ($costGold > 0) {
                    $char->gold -= $costGold;
                    $char->save();
                }

                // 7. Remove Previous Skill (if any) and Update Equipment
                $updatedEquipped = false;
                $equippedSkillsStr = $char->equipment_skills;

                if ($prevSkillId) {
                    CharacterSkill::where('character_id', $charId)
                        ->where('skill_id', $prevSkillId)
                        ->delete();

                    // Check if equipped and swap
                    $equipped = explode(',', $char->equipment_skills);
                    foreach ($equipped as $idx => $id) {
                        if ($id === $prevSkillId) {
                            $equipped[$idx] = $skillId;
                            $updatedEquipped = true;
                        }
                    }
                    if ($updatedEquipped) {
                        $equippedSkillsStr = implode(',', $equipped);
                        $char->equipment_skills = $equippedSkillsStr;
                        $char->save();
                    }
                }

                // 8. Add New Skill
                CharacterSkill::firstOrCreate([
                    'character_id' => $charId,
                    'skill_id' => $skillId
                ]);

                // 9. Get current skills string
                $allSkills = CharacterSkill::where('character_id', $charId)->pluck('skill_id')->toArray();
                $skillsStr = implode(',', $allSkills);

                return (object)[
                    'status' => 1,
                    'result' => "Skill upgraded successfully!",
                    'tokens' => $user->tokens,
                    'account_tokens' => $user->tokens, // Match example
                    'gold' => $char->gold,
                    'skill_id' => $skillId,
                    'old_skill_id' => $prevSkillId,
                    'swapped_id' => $prevSkillId,
                    'skill' => (object)$skillData,
                    'skills' => $skillsStr,
                    'character_skills' => $equippedSkillsStr, // Match example
                    'character_set_skills' => $equippedSkillsStr // Match example
                ];
            });
        } catch (\Exception $e) {
            Log::error("AdvanceAcademyService.upgradeSkill error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getChains()
    {
        return $this->chains;
    }
}
