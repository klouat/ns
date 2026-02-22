<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;

class LearnPetSkillService
{
    public function learnSkill($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petId = $params[2];
        $skillSlot = (int)$params[3]; 
        $learnMethod = $params[4]; 

        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $pet = CharacterPet::where('character_id', $charId)->find($petId);
        if (!$pet) return (object)['status' => 0, 'error' => 'Pet not found'];

        if ($skillSlot < 1 || $skillSlot > 6) {
            return (object)['status' => 0, 'error' => 'Invalid skill slot'];
        }

        $skills = explode(',', $pet->pet_skills);
        
        while (count($skills) < 6) {
            $skills[] = '0';
        }

        $index = $skillSlot - 1;
        $skills[$index] = '1';

        $pet->pet_skills = implode(',', $skills);
        $pet->save();

        return (object)['status' => 1];
    }
}
