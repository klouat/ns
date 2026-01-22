<?php

namespace App\Services\Amf;

use App\Models\User;
use App\Models\Character;
use App\Models\CharacterPet;
use App\Models\CharacterTalentSkill;
use App\Models\CharacterSenjutsuSkill;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SystemLoginService
{
    public function checkVersion($buildNum)
    {
        $ivSource = mt_rand(100000, 999999); 
        $key = Str::random(16);
        
        $data = [
            'status' => 1,
            '_' => $ivSource, 
            '__' => $key,
            'cdn' => '',
            '_rm' => '',
        ];

        return $data;
    }

    public function registerUser($username, $email, $password, $serverString)
    {
        $checkUser = User::where('username', $username)->first();
        if ($checkUser) {
            return [
                'status' => 2,
                'result' => 'Username already exists!'
            ];
        }

        $checkEmail = User::where('email', $email)->first();
        if ($checkEmail) {
            return [
                'status' => 2,
                'result' => 'Email already exists!'
            ];
        }

        try {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->name = $username;
            $user->save();

            return [
                'status' => 1,
                'result' => 'Registered Successfully!'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'error' => 'Internal Server Error'
            ];
        }
    }

    public function loginUser($username, $encryptedPassword, $char_, $bl, $bt, $char__, $item, $seed, $passLen)
    {
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return [
                'status' => 2,
            ];
        }

        $decryptedPassword = $this->decryptPassword($encryptedPassword, $char__, $char_);
        
        if (!$decryptedPassword) {
            return ['status' => 2];
        }

        if (Hash::check($decryptedPassword, $user->password) == false) {
             return ['status' => 2];
        }

        return [
            'status' => 1,
            'uid' => $user->id,
            'sessionkey' => Str::random(32),
            '__' => $char__,
            'events' => [],
            'clan_season' => 1,
            'crew_season' => 1,
            'sw_season' => 1,
            'banners' => []
        ];
    }

    public function getCharacterData($charId, $sessionkey)
    {
        $char = Character::find($charId);

        if (!$char) {
            return [
                'status' => 0,
                'error' => 'Character not found'
            ];
        }

        if ($char->gender == 0) {
            $genderSuffix = '_0';
        } else {
            $genderSuffix = '_1';
        }
        
        $weapon = $char->equipment_weapon;
        if (!$weapon) {
            $weapon = 'wpn_01';
        }

        $backItem = $char->equipment_back;
        if (!$backItem) {
            $backItem = 'back_01';
        }

        $accessory = $char->equipment_accessory;
        if (!$accessory) {
            $accessory = 'accessory_01';
        }

        $clothing = $char->equipment_clothing;
        if (!$clothing) {
            $clothing = 'set_01' . $genderSuffix;
        }
        
        if (is_numeric($char->hair_style)) {
             $hairstyle = 'hair_' . str_pad($char->hair_style, 2, '0', STR_PAD_LEFT) . $genderSuffix;
        } else {
             $hairstyle = $char->hair_style;
             if (!$hairstyle) {
                 $hairstyle = 'hair_01' . $genderSuffix;
             }
        }

        $hairColor = $char->hair_color;
        if (!$hairColor) {
            $hairColor = '0|0';
        }

        $skinColor = $char->skin_color;
        if (!$skinColor) {
            $skinColor = 'null|null';
        }

        return [
            'status' => 1,
            'error' => 0,
            'announcements' => "ngapain yahhh",
            'account_type' => $char->user->account_type ?? 0,
            'emblem_duration' => $char->user->emblem_duration ?? -1,
            'events' => (object)[],
            'has_unread_mails' => false,
            
            'character_data' => [
                'character_id' => $char->id,
                'character_name' => $char->name,
                'character_level' => $char->level,
                'character_xp' => $char->xp,
                'character_gender' => $char->gender,
                'character_rank' => match($char->rank) {
                    'Chunin' => 2,
                    'Tensai Chunin' => 3,
                    'Jounin' => 4,
                    'Tensai Jounin' => 5,
                    'Special Jounin' => 6,
                    'Tensai Special Jounin' => 7,
                    'Ninja Tutor' => 8,
                    'Senior Ninja Tutor' => 9,
                    default => 1
                },
                'character_merit' => 0,
                'character_prestige' => $char->prestige,
                'character_element_1' => $char->element_1,
                'character_element_2' => $char->element_2,
                'character_element_3' => $char->element_3,
                'character_talent_1' => $char->talent_1,
                'character_talent_2' => $char->talent_2,
                'character_talent_3' => $char->talent_3,
                'character_gold' => $char->gold,
                'character_tp' => $char->tp,
                'character_ss' => $char->character_ss,
                'character_class' => null,
                'character_senjutsu' => $char->senjutsu,
                'character_pvp_points' => 0,
                'character_pet_id' => $char->equipped_pet_id ?? 0
            ],
            
            'character_points' => [
                'atrrib_wind' => $char->point_wind,
                'atrrib_fire' => $char->point_fire,
                'atrrib_lightning' => $char->point_lightning,
                'atrrib_water' => $char->point_water,
                'atrrib_earth' => $char->point_earth,
                'atrrib_free' => $char->point_free
            ],
            
            'character_slots' => [
                'weapons' => 100,
                'back_items' => 100,
                'accessories' => 100,
                'hairstyles' => 100,
                'clothing' => 100
            ],
            
            'character_sets' => [
                'weapon' => $weapon,
                'back_item' => $backItem,
                'accessory' => $accessory,
                'hairstyle' => $hairstyle,
                'clothing' => $clothing,
                'skills' => $char->equipment_skills ?? 'skill_01',
                'senjutsu_skills' => $char->equipped_senjutsu_skills,
                'hair_color' => $hairColor,
                'skin_color' => $skinColor,
                'face' => 'face_01' . $genderSuffix,
                'pet' => $char->equipped_pet_id ? \App\Models\CharacterPet::where('character_id', $char->id)->find($char->equipped_pet_id)?->pet_swf : null,
                'anims' => []
            ],
            
            'character_inventory' => [
                'char_weapons' => $this->getInventoryString($char, 'weapon'),
                'char_back_items' => $this->getInventoryString($char, 'back'),
                'char_accessories' => $this->getInventoryString($char, 'accessory'),
                'char_sets' => $this->getInventoryString($char, 'set'),
                'char_hairs' => $this->getCodeString($char, 'hair'), 
                'char_skills' => $this->getSkillsString($char),
                'char_talent_skills' => $this->getTalentSkillsString($char),
                'char_senjutsu_skills' => $this->getSenjutsuSkillsString($char),
                'char_materials' => $this->getInventoryString($char, 'material'),
                'char_items' => $this->getInventoryString($char, 'item'),
                'char_essentials' => $this->getInventoryString($char, 'essential'),
                'char_animations' => ''
            ],
            
            'features' => [
                'pvp'
            ],
            'recruiters' => [],
            'recruit_data' => [],
            'pet_data' => $this->getEquippedPetData($char),
            'clan' => null
        ];
    }

    public function getAllCharacters($uid, $sessionkey)
    {
        $characters = Character::where('user_id', $uid)->get();
        $user = User::find($uid);
        
        $accountData = [];
        
        foreach ($characters as $char) {
            $rank = 1;
            if ($char->rank == 'Chunin') {
                $rank = 2;
            } else if ($char->rank == 'Tensai Chunin') {
                $rank = 3; 
            } else if ($char->rank == 'Jounin') {
                 $rank = 4;
            } else if ($char->rank == 'Tensai Jounin') {
                 $rank = 5;
            } else if ($char->rank == 'Special Jounin') {
                 $rank = 6;
            } else if ($char->rank == 'Tensai Special Jounin') {
                 $rank = 7;
            } else if ($char->rank == 'Ninja Tutor') {
                 $rank = 8;
            } else if ($char->rank == 'Senior Ninja Tutor') {
                 $rank = 9;
            }

            $accountData[] = [
                'char_id' => $char->id,
                'acc_id' => $uid,
                'character_name' => $char->name,
                'character_level' => $char->level,
                'character_xp' => $char->xp,
                'character_gender' => $char->gender,
                'character_rank' => $rank,
                'character_prestige' => $char->prestige,
                'character_element_1' => $char->element_1,
                'character_element_2' => $char->element_2,
                'character_element_3' => $char->element_3,
                'character_talent_1' => $char->talent_1,
                'character_talent_2' => $char->talent_2,
                'character_talent_3' => $char->talent_3,
                'character_gold' => $char->gold,
                'character_tp' => $char->tp,
            ];
        }

        return [
            'status' => 1,
            'error' => 0,
            'account_type' => $user->account_type ?? 0,
            'emblem_duration' => $user->emblem_duration ?? -1,
            'tokens' => $user->tokens ?? 0,
            'total_characters' => count($accountData),
            'account_data' => $accountData
        ];
    }

    private function decryptPassword($encryptedBase64, $keyString, $ivString)
    {
        try {
            $key = $keyString; 
            $iv = $this->pkcs5Pad($ivString, 16);
            $encryptedData = base64_decode($encryptedBase64);
            
            $decrypted = openssl_decrypt(
                $encryptedData, 
                'aes-128-cbc',
                $key, 
                OPENSSL_RAW_DATA, 
                $iv
            );

            return $decrypted;

        } catch (\Exception $e) {
            return false;
        }
    }

    private function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    private function getInventoryString($char, $category)
    {
        $items = \App\Models\CharacterItem::where('character_id', $char->id)
            ->where('category', $category)
            ->get();

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item->item_id . ':' . $item->quantity;
        }

        return implode(',', $parts);
    }

    private function getCodeString($char, $category)
    {
         $items = \App\Models\CharacterItem::where('character_id', $char->id)
            ->where('category', $category)
            ->get();

        $parts = [];
        foreach ($items as $item) {
            $parts[] = $item->item_id;
        }

        return implode(',', $parts);
    }

    private function getSkillsString($char)
    {
        $skills = \App\Models\CharacterSkill::where('character_id', $char->id)->get();
        
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id;
        }

        $result = implode(',', $parts);
        \Illuminate\Support\Facades\Log::info("Skills for char {$char->id}: {$result}");
        file_put_contents('php://stderr', ">>> Skills for char {$char->id}: {$result}\n", FILE_APPEND);
        
        
        return $result;
    }

    public function getTalentSkillsString($char)
    {
        $skills = CharacterTalentSkill::where('character_id', $char->id)->get();
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id . ':' . $skill->level;
        }
        return implode(',', $parts);
    }

    public function getSenjutsuSkillsString($char)
    {
        $skills = \App\Models\CharacterSenjutsuSkill::where('character_id', $char->id)->get();
        $parts = [];
        foreach ($skills as $skill) {
            $parts[] = $skill->skill_id . ':' . $skill->level;
        }
        return implode(',', $parts);
    }

    public function getEquippedPetData($char)
    {
        if (!$char->equipped_pet_id) {
            return [];
        }

        $pet = CharacterPet::where('character_id', $char->id)->find($char->equipped_pet_id);
        if (!$pet) {
            return [];
        }

        return [
            [
                'pet_id' => $pet->id,
                'pet_name' => $pet->pet_name,
                'pet_level' => $pet->pet_level,
                'pet_swf' => $pet->pet_swf,
                'pet_skills' => $pet->pet_skills,
                'pet_mp' => $pet->pet_mp,
                'pet_xp' => $pet->pet_xp,
            ]
        ];
    }
}
