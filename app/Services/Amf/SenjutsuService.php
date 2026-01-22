<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterSenjutsuSkill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SenjutsuService
{
    public function discoverSenjutsu($charId, $sessionKey, $senjutsuType)
    {
        try {
            return DB::transaction(function () use ($charId, $senjutsuType) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return ['status' => 0, 'error' => 'Character not found'];
                }

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) {
                    return ['status' => 0, 'error' => 'User not found'];
                }

                if ($char->senjutsu) {
                    return ['status' => 2, 'result' => 'You already learned a Sage Mode!'];
                }

                // Check type and cost
                $senjutsuType = strtolower($senjutsuType);
                $costGold = 2000000;
                $costTokens = 0;

                if (!in_array($senjutsuType, ['toad', 'snake'])) {
                    return ['status' => 0, 'error' => 'Invalid Senjutsu type'];
                }

                if ($char->gold < $costGold) {
                    return ['status' => 2, 'result' => 'Not enough Gold!'];
                }

                // Process purchase
                $char->gold -= $costGold;
                $char->senjutsu = $senjutsuType;
                $char->save();

                $typeName = ucfirst($senjutsuType) . ' Sage Mode';

                return [
                    'status' => 1,
                    'result' => "You have learned {$typeName}!",
                    'type' => $senjutsuType
                ];
            });
        } catch (\Exception $e) {
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getSenjutsuSkills($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $skills = \App\Models\CharacterSenjutsuSkill::where('character_id', $charId)->get();
        $data = $skills->map(function ($s) use ($char) {
            // Force lowercase and ensure both ID fields are present
            return [
                'id' => (string)$s->skill_id,
                'skill_id' => (string)$s->skill_id,
                'level' => (int)$s->level,
                'type' => strtolower($s->type)
            ];
        })->values()->all();

        return ['status' => 1, 'data' => $data];
    }

    public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        return DB::transaction(function () use ($charId, $skillId, $isMax) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return ['status' => 0, 'error' => 'Character not found'];

            $user = User::find($char->user_id); // Assuming session check passes

            $skill = \App\Models\CharacterSenjutsuSkill::where('character_id', $charId)
                        ->where('skill_id', $skillId)->first();
            
            // If skill doesn't exist, we are learning it at level 1.
            $currentLevel = $skill ? $skill->level : 0;
            $startLevel = $currentLevel + 1;
            $endLevel = $isMax ? 10 : $startLevel;

            if ($startLevel > 10) return ['status' => 0, 'error' => 'Max level reached'];

            // Calculate total cost
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
            
            // Check SS
            if ($char->character_ss < $totalSS) {
                // If isMax, try to upgrade as much as possible
                if ($isMax) {
                    $totalSS = 0;
                    $endLevel = $currentLevel; // Reset end level
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
                         return ['status' => 2, 'result' => 'Not enough Sage Scroll (SS)!'];
                    }
                } else {
                    return ['status' => 2, 'result' => 'Not enough Sage Scroll (SS)!'];
                }
            }

            // Apply Upgrade
            $char->character_ss -= $totalSS;
            $char->save();

            if ($skill) {
                $skill->level = $endLevel;
                $skill->save();
            } else {
                // Create new
                // Determine type: check char senjutsu type, or infer from skill ID? 
                // SenjutsuShop logic: each type has specific skills.
                // Assuming currently equipped senjutsu type is correct for new skills.
                // Or "other" type of common skills.
                // The client filters by type in getSenjutsuSkills but returns "other" too.
                // We'll use char->senjutsu if available, else 'other' if it matches other skills?
                // Actually, logic maps ID to type usually.
                // To keep it simple: use current char senjutsu if matches, else check if it's "other".
                // But `upgradeSkill` happens in SenjutsuProfile, where specific tree is open.
                $type = $char->senjutsu ? strtolower($char->senjutsu) : 'other'; 

                \App\Models\CharacterSenjutsuSkill::create([
                    'character_id' => $charId,
                    'skill_id' => (string)$skillId,
                    'level' => (int)$endLevel,
                    'type' => $type
                ]);
            }

            return [
                'status' => 1, 
                'spent_ss' => (int)$totalSS, 
                'level' => (int)$endLevel,
                'id' => (string)$skillId,
                'skill_id' => (string)$skillId,
                'type' => $char->senjutsu
            ];
        });
    }

    public function buyPackageSS($charId, $sessionKey, $packageIndex)
    {
        return DB::transaction(function () use ($charId, $packageIndex) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return ['status' => 0, 'error' => 'Character not found'];
            $user = User::lockForUpdate()->find($char->user_id);

            $packages = [
                0 => ['price' => 20, 'amount' => 10],
                1 => ['price' => 100, 'amount' => 55],
                2 => ['price' => 200, 'amount' => 120],
                3 => ['price' => 400, 'amount' => 250]
            ];

            if (!isset($packages[$packageIndex])) return ['status' => 0, 'error' => 'Invalid package'];

            $pkg = $packages[$packageIndex];
            
            if ($user->tokens < $pkg['price']) {
                return ['status' => 2, 'result' => 'Not enough Tokens!'];
            }

            $user->tokens -= $pkg['price'];
            $user->save();

            $char->character_ss += $pkg['amount'];
            $char->save();

            return [
                'status' => 1,
                'result' => 'Sage Scroll bought successfully!',
                'ss' => $char->character_ss
            ];
        });
    }

    public function equipSkill($charId, $sessionKey, $skills)
    {
         $char = Character::find($charId);
         if (!$char) return ['status' => 0, 'error' => 'Character not found'];
         
         // $skills is array of skill IDs
         // Validate formatting if needed, join to string
         if (is_array($skills)) {
             $str = implode(',', $skills);
         } else {
             $str = $skills;
         }
         
         $char->equipped_senjutsu_skills = $str;
         $char->save();
         
         return ['status' => 1, 'skills' => $str];
    }

}
