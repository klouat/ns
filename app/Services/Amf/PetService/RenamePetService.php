<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;

class RenamePetService
{
    public function renamePet($charId, $sessionKey, $petId, $newName)
    {
        if (empty($newName)) {
             return (object)['status' => 0, 'error' => 'Invalid name'];
        }

        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $pet = CharacterPet::where('character_id', $charId)->find($petId);
        if (!$pet) return (object)['status' => 0, 'error' => 'Pet not found'];

        $pet->pet_name = $newName;
        $pet->save();

        return (object)['status' => 1];
    }
}
