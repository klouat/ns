<?php

namespace App\Services\Amf\TalentService;

use App\Models\Character;
use App\Models\CharacterTalentSkill;

class GetTalentService
{
    public function getTalentSkills($charId, $sessionKey)
    {
        $skills = CharacterTalentSkill::where('character_id', $charId)->get();

        $data = [];
        foreach ($skills as $skill) {
            $data[] = (object)[
                'item_id' => $skill->skill_id,
                'item_level' => $skill->level,
                'talent_type' => $skill->talent_id
            ];
        }

        $char = Character::find($charId);

        return (object)[
            'status' => 1,
            'data' => $data,
            'current_tp' => $char ? $char->tp : 0
        ];
    }
}
