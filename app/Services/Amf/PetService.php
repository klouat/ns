<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterPet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PetService
{
    public function executeService($action, $params)
    {
        try {
            switch ($action) {
                case 'getPets':
                    return $this->getPets($params);
                case 'equipPet':
                    return $this->equipPet($params);
                case 'unequipPet':
                    return $this->unequipPet($params);
                case 'learnSkill':
                    return $this->learnSkill($params);
                case 'buyPet':
                    return $this->buyPet($params);
                case 'renamePet':
                    return $this->renamePet($params[0], $params[1], $params[2], $params[3]);
                default:
                    return ['status' => 0, 'error' => "Action {$action} not implemented"];
            }
        } catch (\Exception $e) {
            Log::error($e);
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function getPets($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];

        $pets = CharacterPet::where('character_id', $charId)->get();

        $petList = [];
        foreach ($pets as $pet) {
            $petList[] = [
                'pet_id' => $pet->id,
                'pet_name' => $pet->pet_name,
                'pet_level' => $pet->pet_level,
                'pet_swf' => $pet->pet_swf,
                'pet_skills' => $pet->pet_skills,
                'pet_mp' => $pet->pet_mp,
                'pet_xp' => $pet->pet_xp,
            ];
        }

        return [
            'status' => 1,
            'pets' => $petList
        ];
    }

    private function equipPet($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petId = $params[2];

        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $pet = CharacterPet::where('character_id', $charId)->find($petId);
        if (!$pet) return ['status' => 0, 'error' => 'Pet not found'];

        $char->equipped_pet_id = $petId;
        $char->save();

        return [
            'status' => 1,
            'pet_id' => $petId
        ];
    }

    private function unequipPet($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];

        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $char->equipped_pet_id = null;
        $char->save();

        return [
            'status' => 1
        ];
    }

    private function learnSkill($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petId = $params[2];
        $skillSlot = (int)$params[3]; // 1-based index from client (1 to 6)
        $learnMethod = $params[4]; // "mc1" or "mc2"

        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $pet = CharacterPet::where('character_id', $charId)->find($petId);
        if (!$pet) return ['status' => 0, 'error' => 'Pet not found'];

        if ($skillSlot < 1 || $skillSlot > 6) {
            return ['status' => 0, 'error' => 'Invalid skill slot'];
        }

        $skills = explode(',', $pet->pet_skills);
        
        // Ensure array has 6 elements
        while (count($skills) < 6) {
            $skills[] = '0';
        }

        // Update skill status to learned (1)
        // Note: Slot 1 is usually the basic attack and is always learned (1).
        // Slots 2-6 are learnable.
        $index = $skillSlot - 1;
        $skills[$index] = '1';

        $pet->pet_skills = implode(',', $skills);
        $pet->save();

        // TODO: Deduct resources (Gold + Material for mc1, Tokens for mc2)
        // Currently bypassing deduction to ensure skill learning works first.

        return ['status' => 1];
    }

    private function buyPet($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petSwf = $params[2]; // e.g. "pet_yamaru"

        $char = Character::lockForUpdate()->find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $user = \App\Models\User::lockForUpdate()->find($char->user_id);
        if (!$user) return ['status' => 0, 'error' => 'User not found'];

        // 1. Find price from gamedata.json
        $json = file_get_contents(storage_path('app/gamedata.json'));
        $data = json_decode($json, true);
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

        if (!$priceStr) return ['status' => 0, 'error' => 'Pet price not found'];

        // 2. Validate resources
        $price = 0;
        if (str_starts_with($priceStr, 'gold_')) {
            $price = (int)str_replace(['gold_', 'M'], ['', '000000'], $priceStr);
            if ($char->gold < $price) return ['status' => 2, 'result' => 'Not enough Gold'];
            $char->gold -= $price;
        } else if (str_starts_with($priceStr, 'token_')) {
            $price = (int)str_replace('token_', '', $priceStr);
            if ($user->tokens < $price) return ['status' => 2, 'result' => 'Not enough Tokens'];
            $user->tokens -= $price;
        }

        // 3. Create pet
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

        return [
            'status' => 1,
            'pet_id' => $pet->id
        ];
    }

    public function renamePet($charId, $sessionKey, $petId, $newName)
    {
        if (empty($newName)) {
             return ['status' => 0, 'error' => 'Invalid name'];
        }

        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];

        $pet = CharacterPet::where('character_id', $charId)->find($petId);
        if (!$pet) return ['status' => 0, 'error' => 'Pet not found'];

        $pet->pet_name = $newName;
        $pet->save();

        return ['status' => 1];
    }
}
