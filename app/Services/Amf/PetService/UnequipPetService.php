<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class UnequipPetService
{
    public function unequipPet($params)
    {
        return DB::transaction(function() use ($params) {
            $charId = $params[0];
            $sessionKey = $params[1];

            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

            $char->equipped_pet_id = null;
            $char->save();

            return (object)[
                'status' => 1
            ];
        });
    }
}
