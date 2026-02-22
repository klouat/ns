<?php

namespace App\Services\Amf\TalentService;

use App\Models\Character;
use App\Models\CharacterTalentSkill;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpgradeTalentService
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

    public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        try {
            return DB::transaction(function () use ($charId, $skillId, $isMax) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $gameData = GameDataHelper::get_gamedata();
                
                $foundTalentKey = null;

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
                    if (preg_match('/^talent_(.+)_skill_\d+$/', $foundTalentKey, $matches)) {
                        $extractedId = $matches[1];
                        
                        $slots = [
                            'talent_1' => $char->talent_1, 
                            'talent_2' => $char->talent_2, 
                            'talent_3' => $char->talent_3
                        ];

                        foreach ($slots as $col => $val) {
                            if ($val === $extractedId || $val === 'talent_' . $extractedId) {
                                $talentId = $val; 
                                break;
                            }
                        }
                    }
                }

                if (!$talentId) {
                    $existing = CharacterTalentSkill::where('character_id', $charId)
                        ->where('skill_id', $skillId)
                        ->first();
                    if ($existing) {
                        $talentId = $existing->talent_id;
                    }
                }

                if (!$talentId) {
                     return (object)['status' => 2, 'result' => 'You have not learned the talent associated with this skill.'];
                }

                $skillEntry = CharacterTalentSkill::where('character_id', $charId)
                    ->where('skill_id', $skillId)
                    ->first();

                $currentLevel = $skillEntry ? $skillEntry->level : 0;
                $targetLevel = $currentLevel + 1;

                if ($targetLevel > 10) {
                    return (object)['status' => 2, 'result' => 'Skill is already max level.'];
                }

                $tpCost = $this->getTpCost($targetLevel);
                
                if ($char->tp < $tpCost) {
                    return (object)['status' => 2, 'result' => 'Not enough TP.'];
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

                return (object)[
                    'status' => 1,
                    'current_tp' => $char->tp
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
