<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;
use Illuminate\Support\Facades\Log;

class GetPetsService
{
    public function getPets($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];

        $char = Character::find($charId);
        $pets = CharacterPet::where('character_id', $charId)->get();

        $equippedId = $char ? $char->equipped_pet_id : null;

        Log::info("PetService::getPets charId: $charId, equippedId: " . json_encode($equippedId));

        $petList = [];
        foreach ($pets as $pet) {
            $isEquipped = ($equippedId !== null && (string)$equippedId === (string)$pet->id) ? 1 : 0;
            
            if ($isEquipped) {
                 Log::info("  > Found equipped pet: {$pet->id} ({$pet->pet_name})");
            }

            $petList[] = (object)[
                'pet_id' => $pet->id,
                'pet_name' => $pet->pet_name,
                'pet_level' => $pet->pet_level,
                'pet_type' => 1,
                'pet_status' => $isEquipped, 
                'is_equipped' => $isEquipped, 
                'status' => $isEquipped, 
                'pet_swf' => $pet->pet_swf,
                'pet_skills' => $pet->pet_skills,
                'pet_mp' => $pet->pet_mp,
                'pet_xu' => 0,
                'pet_xp' => $pet->pet_xp,
                'trn_cost' => 0 
            ];
        }

        return (object)[
            'status' => 1,
            'character_pet_id' => $equippedId, 
            'result' => 'ok', 
            'pets' => $petList
        ];
    }
}
