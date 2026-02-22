<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;
use Illuminate\Support\Facades\DB;

class EquipPetService
{
    public function equipPet($params)
    {
        return DB::transaction(function() use ($params) {
            $charId = $params[0];
            $sessionKey = $params[1];
            $petId = $params[2];

            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

            $pet = CharacterPet::where('character_id', $charId)->find($petId);
            if (!$pet) return (object)['status' => 0, 'error' => 'Pet not found'];

            $char->equipped_pet_id = $petId;
            $char->save();

            return (object)[
                'status' => 1,
                'pet_id' => $petId
            ];
        });
    }
}
