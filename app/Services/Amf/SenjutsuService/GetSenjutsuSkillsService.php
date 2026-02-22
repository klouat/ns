<?php

namespace App\Services\Amf\SenjutsuService;

use App\Models\Character;
use App\Models\CharacterSenjutsuSkill;

class GetSenjutsuSkillsService
{
    public function getSenjutsuSkills($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $skills = CharacterSenjutsuSkill::where('character_id', $charId)->get();
        $data = $skills->map(function ($s) use ($char) {
            return (object)[
                'id' => (string)$s->skill_id,
                'skill_id' => (string)$s->skill_id,
                'level' => (int)$s->level,
                'type' => strtolower($s->type)
            ];
        })->values()->all();

        return (object)['status' => 1, 'data' => $data];
    }
}
