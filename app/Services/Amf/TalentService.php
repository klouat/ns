<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterTalentSkill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TalentService
{
    private function getTpCost($level)
    {
        return match ($level) {
            1 => 5,
            2 => 10,
            3 => 25,
            4 => 50,
            5 => 100,
            6 => 200,
            7 => 300,
            8 => 450,
            9 => 600,
            10 => 800,
            default => 0,
        };
    }

    public function getTalentSkills($charId, $sessionKey)
    {
        $skills = CharacterTalentSkill::where('character_id', $charId)->get();

        $data = [];
        foreach ($skills as $skill) {
            $data[] = [
                'item_id' => $skill->skill_id,
                'item_level' => $skill->level,
                'talent_type' => $skill->talent_id
            ];
        }

        $char = Character::find($charId);

        return [
            'status' => 1,
            'data' => $data,
            'current_tp' => $char ? $char->tp : 0
        ];
    }

 public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        try {
            return DB::transaction(function () use ($charId, $skillId, $isMax) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                // 1. Load Game Data
                $json = file_get_contents(storage_path('app/gamedata.json'));
                $gameData = json_decode($json, true);
                
                $foundTalentKey = null;

                // 2. Find the key containing the skill (e.g., "talent_lm_skill_1")
                foreach ($gameData as $section) {
                    if (isset($section['data']) && is_array($section['data'])) {
                        foreach ($section['data'] as $key => $val) {
                            if (isset($val['talent_skill_id']) && $val['talent_skill_id'] === $skillId) {
                                $foundTalentKey = $key;
                                break 2;
                            }
                        }
                    }
                }

                $talentId = null;

                if ($foundTalentKey) {
                    // 3. PARSE LOGIC: Extract "lm" from "talent_lm_skill_1"
                    // We look for the string between "talent_" and "_skill_"
                    if (preg_match('/^talent_(.+)_skill_\d+$/', $foundTalentKey, $matches)) {
                        $extractedId = $matches[1]; // This is "lm"
                        
                        // 4. Compare strictly against user slots
                        // We check if the user has "lm" (or "talent_lm") in their slots
                        $slots = [
                            'talent_1' => $char->talent_1, 
                            'talent_2' => $char->talent_2, 
                            'talent_3' => $char->talent_3
                        ];

                        foreach ($slots as $col => $val) {
                            // Check exact match to "lm" OR "talent_lm" just to be safe
                            if ($val === $extractedId || $val === 'talent_' . $extractedId) {
                                $talentId = $val; // Keep the format stored in DB
                                break;
                            }
                        }
                    }
                }

                // Fallback: If not found in JSON, check if skill exists in DB
                if (!$talentId) {
                    $existing = CharacterTalentSkill::where('character_id', $charId)
                        ->where('skill_id', $skillId)
                        ->first();
                    if ($existing) {
                        $talentId = $existing->talent_id;
                    }
                }

                if (!$talentId) {
                     return ['status' => 2, 'result' => 'You have not learned the talent associated with this skill.'];
                }

                // --- Existing Update Logic ---

                $skillEntry = CharacterTalentSkill::where('character_id', $charId)
                    ->where('skill_id', $skillId)
                    ->first();

                $currentLevel = $skillEntry ? $skillEntry->level : 0;
                $targetLevel = $currentLevel + 1;

                if ($targetLevel > 10) {
                    return ['status' => 2, 'result' => 'Skill is already max level.'];
                }

                $tpCost = $this->getTpCost($targetLevel);
                
                if ($char->tp < $tpCost) {
                    return ['status' => 2, 'result' => 'Not enough TP.'];
                }

                $char->tp -= $tpCost;
                
                if ($skillEntry) {
                    $skillEntry->level = $targetLevel;
                    $skillEntry->talent_id = $talentId;
                    $skillEntry->save();
                } else {
                    CharacterTalentSkill::create([
                        'character_id' => $charId,
                        'skill_id' => $skillId,
                        'talent_id' => $talentId,
                        'level' => $targetLevel
                    ]);
                }

                $char->save();
                
                if ($isMax == 1) {
                    while ($targetLevel < 10) {
                        $nextLevel = $targetLevel + 1;
                        $nextCost = $this->getTpCost($nextLevel);
                        
                        if ($char->tp >= $nextCost) {
                            $char->tp -= $nextCost;
                            $targetLevel = $nextLevel;
                            
                            CharacterTalentSkill::where('character_id', $charId)
                                ->where('skill_id', $skillId)
                                ->update(['level' => $targetLevel]);
                            
                            $char->save();
                        } else {
                            break;
                        }
                    }
                }

                return [
                    'status' => 1,
                    'current_tp' => $char->tp
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
    
    public function buyPackageTP($charId, $sessionKey, $packageId)
    {
        try {
            return DB::transaction(function () use ($charId, $packageId) {
                // Costs and Rewards based on TalentBoost.as
                $costs = [20, 100, 200, 400];
                $rewards = [20, 125, 250, 600];

                if (!isset($costs[$packageId])) {
                     return ['status' => 2, 'result' => 'Invalid package ID'];
                }

                $cost = $costs[$packageId];
                $reward = $rewards[$packageId];

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = \App\Models\User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $cost) {
                    return ['status' => 2, 'result' => 'Not enough tokens'];
                }

                $user->tokens -= $cost;
                $user->save();

                $char->tp += $reward;
                $char->save();

                return [
                    'status' => 1,
                    'price' => $cost,
                    'add' => $reward,
                    'current_tp' => $char->tp,
                    'current_tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function discoverTalent($charId, $sessionKey, $type, $targetTalent)
    {
        try {
            return DB::transaction(function () use ($charId, $type, $targetTalent) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return ['status' => 0, 'error' => 'User not found'];

                // 1. Validation: Level
                $minLevel = ($type === 'Extreme') ? 40 : 50;
                if ($char->level < $minLevel) {
                    return ['status' => 2, 'result' => "You must be Level $minLevel to learn this!"];
                }

                // 2. Fetch Talent Info from gamedata.json
                $gameDataContent = file_get_contents(storage_path('app/gamedata.json'));
                $gameData = json_decode($gameDataContent, true);
                
                $talentInfo = null;
                foreach ($gameData as $section) {
                    if ($section['id'] === 'talent_info') {
                        $talentInfo = $section['data'][$targetTalent] ?? null;
                        break;
                    }
                }

                if (!$talentInfo) {
                    return ['status' => 0, 'error' => 'Talent data not found!'];
                }

                // 3. Validation: Emblem Requirement
                if ($talentInfo['is_emblem'] && $user->account_type == 0) {
                    return ['status' => 2, 'result' => 'Upgrade to Emblem to learn this talent!'];
                }

                // 4. Validation: Gold and Tokens
                $priceGold = $talentInfo['price_gold'] ?? 0;
                $priceTokens = $talentInfo['price_token'] ?? 0;

                if ($char->gold < $priceGold || $user->tokens < $priceTokens) {
                    return ['status' => 2, 'result' => 'Not enough resources!'];
                }

                // 5. Check slot availability
                $newt = 1;
                if ($type === 'Extreme') {
                    if ($char->talent_1 != null) return ['status' => 2, 'result' => 'You already have an Extreme Talent!'];
                    $char->talent_1 = $targetTalent;
                    $newt = 1;
                } else if ($type === 'Secret') {
                    if ($char->talent_2 == null) {
                        $char->talent_2 = $targetTalent;
                        $newt = 2;
                    } else if ($char->talent_3 == null) {
                        // Secret slot 2 usually requires rank 6 (Special Jounin) or higher
                        $rankNum = match($char->rank) {
                            'Chunin' => 2,
                            'Tensai Chunin' => 3,
                            'Jounin' => 4,
                            'Tensai Jounin' => 5,
                            'Special Jounin' => 6,
                            'Tensai Special Jounin' => 7,
                            'Ninja Tutor' => 8,
                            'Senior Ninja Tutor' => 9,
                            default => 1
                        };
                        
                        if ($rankNum < 6) {
                            return ['status' => 2, 'result' => 'Must reach Special Jounin rank for the second Secret Talent slot!'];
                        }
                        
                        $char->talent_3 = $targetTalent;
                        $newt = 3;
                    } else {
                        return ['status' => 2, 'result' => 'No empty talent slots!'];
                    }
                }

                // Deduct resources
                $char->gold -= $priceGold;
                $user->tokens -= $priceTokens;

                $char->save();
                $user->save();

                return [
                    'status' => 1,
                    'tokens' => $user->tokens,
                    'golds' => $char->gold,
                    'newt' => $newt
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}