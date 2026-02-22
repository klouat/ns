<?php

namespace App\Services\Amf\ConfrontingDeathEvent2025Service;

use App\Models\Character;
use App\Models\CharacterSkill;
use App\Models\User;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;

class SkillService
{
    private array $trainingSkills = [
        ['id' => 'skill_2313', 'price' => [3999, 2999], 'name' => 'Erebos Beam'],
        ['id' => 'skill_2314', 'price' => [1999, 1999], 'name' => 'Advance Erebos Beam']
    ];

    public function buySkill($charId, $sessionKey, $skillIndex)
    {
        return DB::transaction(function () use ($charId, $skillIndex) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            
            if (!isset($this->trainingSkills[$skillIndex])) {
                 return (object)['status' => 0, 'error' => 'Skill not found'];
            }
            
            $skillInfo = $this->trainingSkills[$skillIndex];
            
            // account_type: 0 = Normal, 1 = Emblem
            $priceIndex = ($user->account_type == 1) ? 1 : 0;
            $price = $skillInfo['price'][$priceIndex];
            
            if ($user->tokens < $price) {
                return (object)['status' => 2, 'result' => 'Not enough tokens'];
            }
            
            $user->tokens -= $price;
            $user->save();
            
            // If buying Advance Erebos Beam, remove the base one
            if ($skillInfo['id'] === 'skill_2314') {
                CharacterSkill::where('character_id', $charId)
                    ->where('skill_id', 'skill_2313')
                    ->delete();

                // Also remove from equipped skills string if present
                if ($char->equipment_skills) {
                    $skills = explode(',', $char->equipment_skills);
                    $skills = array_filter($skills, fn($s) => $s !== 'skill_2313');
                    $char->equipment_skills = implode(',', $skills);
                    $char->save();
                }
            }
            
            CharacterSkill::firstOrCreate([
                'character_id' => $charId,
                'skill_id' => $skillInfo['id']
            ]);
            
            return (object)['status' => 1];
        });
    }
}
