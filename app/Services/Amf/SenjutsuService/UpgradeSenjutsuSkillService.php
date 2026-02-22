<?php

namespace App\Services\Amf\SenjutsuService;

use App\Models\Character;
use App\Models\User;
use App\Models\CharacterSenjutsuSkill;
use Illuminate\Support\Facades\DB;

class UpgradeSenjutsuSkillService
{
    public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        return DB::transaction(function () use ($charId, $skillId, $isMax) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

            $user = User::find($char->user_id); 

            $skill = CharacterSenjutsuSkill::where('character_id', $charId)
                        ->where('skill_id', $skillId)->first();
            
            $currentLevel = $skill ? $skill->level : 0;
            $startLevel = $currentLevel + 1;
            $endLevel = $isMax ? 10 : $startLevel;

            if ($startLevel > 10) return (object)['status' => 0, 'error' => 'Max level reached'];

            $totalSS = 0;
            $costs = [
                1 => 5, 2 => 10, 3 => 25, 4 => 50, 5 => 100,
                6 => 200, 7 => 300, 8 => 450, 9 => 600, 10 => 800
            ];

            for ($lvl = $startLevel; $lvl <= $endLevel; $lvl++) {
                 if (isset($costs[$lvl])) {
                     $totalSS += $costs[$lvl];
                 }
            }
            
            if ($char->character_ss < $totalSS) {
                if ($isMax) {
                    $totalSS = 0;
                    $endLevel = $currentLevel; 
                    for ($lvl = $startLevel; $lvl <= 10; $lvl++) {
                        $cost = $costs[$lvl];
                        if ($char->character_ss >= ($totalSS + $cost)) {
                            $totalSS += $cost;
                            $endLevel = $lvl;
                        } else {
                            break;
                        }
                    }
                    if ($endLevel == $currentLevel) {
                         return (object)['status' => 2, 'result' => 'Not enough Sage Scroll (SS)!'];
                    }
                } else {
                    return (object)['status' => 2, 'result' => 'Not enough Sage Scroll (SS)!'];
                }
            }

            $char->character_ss -= $totalSS;
            $char->save();

            if ($skill) {
                $skill->level = $endLevel;
                $skill->save();
            } else {
                $type = $char->senjutsu ? strtolower($char->senjutsu) : 'other'; 

                CharacterSenjutsuSkill::create([
                    'character_id' => $charId,
                    'skill_id' => (string)$skillId,
                    'level' => (int)$endLevel,
                    'type' => $type
                ]);
            }

            return (object)[
                'status' => 1, 
                'spent_ss' => (int)$totalSS, 
                'level' => (int)$endLevel,
                'id' => (string)$skillId,
                'skill_id' => (string)$skillId,
                'type' => $char->senjutsu
            ];
        });
    }
}
