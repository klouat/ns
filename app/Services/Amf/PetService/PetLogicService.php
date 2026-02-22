<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PetLogicService
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

    public function buyPet($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petSwf = $params[2]; 

        $char = Character::lockForUpdate()->find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $user = \App\Models\User::lockForUpdate()->find($char->user_id);
        if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

        $data = GameDataHelper::get_gamedata();
        $priceStr = null;
        foreach ($data as $section) {
            if ($section['id'] === 'pet_shop' || $section['id'] === 'tailed_beast') {
                foreach ($section['data']['pets'] as $p) {
                    if ($p['id'] === $petSwf) {
                        $priceStr = $p['price'];
                        break (2);
                    }
                }
            }
        }

        if (!$priceStr) return (object)['status' => 0, 'error' => 'Pet price not found'];

        $price = 0;
        if (str_starts_with($priceStr, 'gold_')) {
            $price = (int)str_replace(['gold_', 'M'], ['', '000000'], $priceStr);
            if ($char->gold < $price) return (object)['status' => 2, 'result' => 'Not enough Gold'];
            $char->gold -= $price;
        } else if (str_starts_with($priceStr, 'token_')) {
            $price = (int)str_replace('token_', '', $priceStr);
            if ($user->tokens < $price) return (object)['status' => 2, 'result' => 'Not enough Tokens'];
            $user->tokens -= $price;
        }

        $pet = CharacterPet::create([
            'character_id' => $charId,
            'pet_swf' => $petSwf,
            'pet_name' => ucwords(str_replace('pet_', '', $petSwf)),
            'pet_level' => 1,
            'pet_xp' => 0,
            'pet_mp' => 0,
            'pet_skills' => '1,0,0,0,0,0'
        ]);

        $char->save();
        $user->save();

        return (object)[
            'status' => 1,
            'pet_id' => $pet->id
        ];
    }

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
