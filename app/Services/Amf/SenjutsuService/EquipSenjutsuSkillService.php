<?php

namespace App\Services\Amf\SenjutsuService;

use App\Models\Character;

class EquipSenjutsuSkillService
{
    public function equipSkill($charId, $sessionKey, $skills)
    {
         $char = Character::find($charId);
         if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
         
         if (is_array($skills)) {
             $str = implode(',', $skills);
         } else {
             $str = $skills;
         }
         
         $char->equipped_senjutsu_skills = $str;
         $char->save();
         
         return (object)['status' => 1, 'skills' => $str];
    }
}
