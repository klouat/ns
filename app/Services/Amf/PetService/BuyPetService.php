<?php

namespace App\Services\Amf\PetService;

use App\Models\Character;
use App\Models\CharacterPet;
use App\Models\User;
use App\Helpers\GameDataHelper;

class BuyPetService
{
    public function buyPet($params)
    {
        $charId = $params[0];
        $sessionKey = $params[1];
        $petSwf = $params[2]; 

        $char = Character::lockForUpdate()->find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $user = User::lockForUpdate()->find($char->user_id);
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
}
