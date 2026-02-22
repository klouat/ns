<?php

namespace App\Services\Amf\SystemLoginService;

use App\Models\User;
use App\Models\Character;
use App\Models\CharacterPet;
use App\Models\CharacterTalentSkill;
use App\Models\CharacterSenjutsuSkill;
use App\Services\Amf\FriendService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginLogicService
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

        return (object)$data;
    }

    public function registerUser($username, $email, $password, $serverString)
    {
        $checkUser = User::where('username', $username)->first();
        if ($checkUser) {
            return (object)[
                'status' => 2,
                'result' => 'Username already exists!'
            ];
        }

        $checkEmail = User::where('email', $email)->first();
        if ($checkEmail) {
            return (object)[
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

            return (object)[
                'status' => 1,
                'result' => 'Registered Successfully!'
            ];
        } catch (\Exception $e) {
            return (object)[
                'status' => 0,
                'error' => 'Internal Server Error'
            ];
        }
    }

    public function loginUser($username, $encryptedPassword, $char_, $bl, $bt, $char__, $item, $seed, $passLen)
    {
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return (object)[
                'status' => 2,
            ];
        }

        $decryptedPassword = $this->decryptPassword($encryptedPassword, $char__, $char_);
        
        if (!$decryptedPassword) {
            return (object)['status' => 2];
        }

        if (Hash::check($decryptedPassword, $user->password) == false) {
             return (object)['status' => 2];
        }

        $sessionKey = Str::random(32);
        $user->remember_token = $sessionKey;
        $user->save();

        return (object)[
            'status' => 1,
            'uid' => $user->id,
            'sessionkey' => $sessionKey,
            '__' => $char__,
            'events' => [
                'welcome_bonus',
                'mysterious-market',
                'chunin_package',
                'special-deals',
                'monster_hunter_2023',
                'dragon_hunt_2024',
                'justice-badge2024',
                'giveaway-center',
                'leaderboard',
                'tailedbeast',
                'dailygacha',
                'dragongacha',
                'exoticpackage',
                'thanksgiving2025',
                'elementalars',
                'xmass2025',
                'valentine2026',
                'phantom_kyunoki_2026',
            ],
            'clan_season' => 67,
            'crew_season' => 67,
            'sw_season' => 67,
            'banners' => []
        ];
    }

    public function getCharacterData($charId, $sessionkey)
    {
        $char = Character::with(['user', 'items', 'skills', 'talent_skills', 'senjutsu_skills', 'pets'])
            ->find($charId);

        if ($char && !$this->validateSession($char->user_id, $sessionkey)) {
             return (object)['status' => 0, 'error' => 'Session expired!'];
        }

        if (!$char) {
            return (object)[
                'status' => 0,
                'error' => 'Character not found'
            ];
        }

        $genderSuffix = $char->gender == 0 ? '_0' : '_1';
        
        $weapon    = $char->equipment_weapon    ?: 'wpn_01';
        $backItem  = $char->equipment_back      ?: 'back_01';
        $accessory = $char->equipment_accessory ?: 'accessory_01';
        $clothing  = $char->equipment_clothing  ?: 'set_01' . $genderSuffix;

        if (is_numeric($char->hair_style)) {
             $hairstyle = 'hair_' . str_pad($char->hair_style, 2, '0', STR_PAD_LEFT) . $genderSuffix;
        } else {
             $hairstyle = $char->hair_style ?: 'hair_01' . $genderSuffix;
        }

        $hairColor = $char->hair_color ?: '0|0';
        $skinColor = $char->skin_color ?: 'null|null';
        $colorHex  = $char->name_color ?: '#000000';

        $items_grouped = $char->items->groupBy('category');

        $char_weapons    = $this->buildInventoryFromGroup($items_grouped, 'weapon');
        $char_back_items = $this->buildInventoryFromGroup($items_grouped, 'back');
        $char_accessories = $this->buildInventoryFromGroup($items_grouped, 'accessory');
        $char_sets       = $this->buildInventoryFromGroup($items_grouped, 'set');
        $char_materials  = $this->buildInventoryFromGroup($items_grouped, 'material');
        $char_items      = $this->buildInventoryFromGroup($items_grouped, 'item');
        $char_essentials = $this->buildInventoryFromGroup($items_grouped, 'essential');
        $char_hairs      = $this->buildCodeFromGroup($items_grouped, 'hair');
        $char_animations = $this->buildCodeFromGroup($items_grouped, 'animation');

        $char_skills = $char->skills->pluck('skill_id')->implode(',');

        $char_talent_skills = $char->talent_skills
            ->map(fn($s) => $s->skill_id . ':' . $s->level)
            ->implode(',');

        $char_senjutsu_skills = $char->senjutsu_skills
            ->map(fn($s) => $s->skill_id . ':' . $s->level)
            ->implode(',');

        $equipped_pet_obj = $this->getEquippedPetData($char);
        $pet_swf = $equipped_pet_obj->pet_swf ?? null;
        
        return (object)[
            'status' => 1,
            'error' => 0,
            'announcements' => "ngapain yahhh",
            'account_type' => $char->user->account_type ?? 0,
            'emblem_duration' => $char->user->emblem_duration ?? -1,
            'events' => [
                'welcome_bonus',
                'mysterious-market',
                'chunin_package',
                'special-deals',
                'monster_hunter_2023',
                'dragon_hunt_2024',
                'justice-badge2024',
                'giveaway-center',
                'leaderboard',
                'tailedbeast',
                'dailygacha',
                'dragongacha',
                'exoticpackage',
                'thanksgiving2025',
                'elementalars',
                'xmass2025',
                'valentine2026',
                'phantom_kyunoki_2026',
            ],
            'has_unread_mails' => false,
            'clan_season' => 1,
            'crew_season' => 1,
            'sw_season' => 1,
            'banners' => [],
            'character_data' => (object)[
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
                    'Sage' => 10,
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
                'character_class' => $char->class,
                'character_senjutsu' => $char->senjutsu,
                'character_pvp_points' => 0,
                'character_pet_id' => $char->equipped_pet_id ?? 0, 
                'character_pet' => $pet_swf 
            ],
            'rgb_data' => [
                [
                    'id' => (string)$char->id,
                    'data' => $colorHex
                ]
            ],
            'character_points' => (object)[
                'atrrib_wind' => $char->point_wind,
                'atrrib_fire' => $char->point_fire,
                'atrrib_lightning' => $char->point_lightning,
                'atrrib_water' => $char->point_water,
                'atrrib_earth' => $char->point_earth,
                'atrrib_free' => $char->point_free
            ],
            
            'character_slots' => (object)[
                'weapons' => 100,
                'back_items' => 100,
                'accessories' => 100,
                'hairstyles' => 100,
                'clothing' => 100
            ],
            
            'character_sets' => (object)[
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
                'pet' => $pet_swf,
                'character_pet_id' => $char->equipped_pet_id ? (int)$char->equipped_pet_id : 0, 
                'anims' => $char->equipped_animations ? (object)json_decode($char->equipped_animations, true) : (object)[]
            ],
            
            'character_inventory' => (object)[
                'char_weapons' => $char_weapons,
                'char_back_items' => $char_back_items,
                'char_accessories' => $char_accessories,
                'char_sets' => $char_sets,
                'char_hairs' => $char_hairs,
                'char_skills' => $char_skills,
                'char_talent_skills' => $char_talent_skills,
                'char_senjutsu_skills' => $char_senjutsu_skills,
                'char_materials' => $char_materials,
                'char_items' => $char_items,
                'char_essentials' => $char_essentials,
                'char_animations' => $char_animations
            ],
            
            'features' => [
                'pvp'
            ],
            'recruiters' => $this->getRecruiters($char),
            'recruit_data' => $this->getRecruitData($char),
            'pet_data' => $equipped_pet_obj,
            'clan' => null
        ];
    }

    private function buildInventoryFromGroup($grouped, string $category): string
    {
        if (!isset($grouped[$category])) {
            return '';
        }

        return $grouped[$category]
            ->map(fn($item) => $item->item_id . ':' . $item->quantity)
            ->implode(',');
    }

    private function buildCodeFromGroup($grouped, string $category): string
    {
        $parts = [];
        if (isset($grouped[$category])) {
            $parts = $grouped[$category]->pluck('item_id')->toArray();
        }

        if ($category === 'animation') {
             $defaultAnimations = ['ani_1', 'ani_3', 'ani_5', 'ani_7', 'ani_9', 'ani_10', 'ani_11', 'ani_14'];
             $parts = array_unique(array_merge($parts, $defaultAnimations));
        }

        return implode(',', $parts);
    }

    private function getRecruiters($char)
    {
        $recruits = $char->recruits ?? [];
        
        if (empty($recruits)) {
            return [];
        }
        
        $recruitObjects = array_map(function($id) {
            $stringId = str_starts_with($id, 'npc_') ? $id : 'char_' . $id;
            return (object)['recruited_char_id' => $stringId];
        }, $recruits);
        
        $hash = hash('sha256', (string)$recruitObjects[0]->recruited_char_id);
        return [$recruitObjects, $hash];
    }

    private function getRecruitData($char)
    {
        $recruits = $char->recruits ?? [];
        if (empty($recruits)) {
            return [];
        }

        // Must be fully qualified or imported.
        $friendService = new FriendService();
        $data = [];

        $characters = Character::with('user')
            ->whereIn('id', $recruits)
            ->get()
            ->keyBy('id');
        
        foreach ($recruits as $id) {
            $c = $characters->get($id);
            if ($c) {
                $data[] = $friendService->formatFriendData($c);
            }
        }
        
        return $data;
    }

    public function getAllCharacters($uid, $sessionkey)
    {
        if (!$this->validateSession($uid, $sessionkey)) {
            return (object)['status' => 0, 'error' => 'Session expired!'];
        }

        $characters = Character::where('user_id', $uid)->get();
        $user = User::find($uid);
        
        $accountData = [];
        
        foreach ($characters as $char) {
            $rank = match($char->rank) {
                'Chunin'                => 2,
                'Tensai Chunin'         => 3,
                'Jounin'                => 4,
                'Tensai Jounin'         => 5,
                'Special Jounin'        => 6,
                'Tensai Special Jounin' => 7,
                'Ninja Tutor'           => 8,
                'Senior Ninja Tutor'    => 9,
                default                 => 1
            };

            $accountData[] = (object)[
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

        return (object)[
            'status' => 1,
            'error' => 0,
            'account_type' => $user->account_type ?? 0,
            'emblem_duration' => $user->emblem_duration ?? -1,
            'tokens' => $user->tokens ?? 0,
            'total_characters' => count($accountData),
            'account_data' => $accountData
        ];
    }

    public function decryptPassword($encryptedBase64, $keyString, $ivString)
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

    public function getTalentSkillsString($char)
    {
        $skills = $char->relationLoaded('talent_skills')
            ? $char->talent_skills
            : CharacterTalentSkill::where('character_id', $char->id)->get();

        return $skills->map(fn($s) => $s->skill_id . ':' . $s->level)->implode(',');
    }

    public function getSenjutsuSkillsString($char)
    {
        $skills = $char->relationLoaded('senjutsu_skills')
            ? $char->senjutsu_skills
            : CharacterSenjutsuSkill::where('character_id', $char->id)->get();

        return $skills->map(fn($s) => $s->skill_id . ':' . $s->level)->implode(',');
    }

    public function getEquippedPetData($char)
    {
        if (!$char->equipped_pet_id) {
            return (object)[];
        }

        $pet = $char->relationLoaded('pets')
            ? $char->pets->firstWhere('id', $char->equipped_pet_id)
            : CharacterPet::where('character_id', $char->id)->find($char->equipped_pet_id);

        if (!$pet) {
            return (object)[];
        }

        return (object)[
            'pet_id'     => $pet->id,
            'pet_name'   => $pet->pet_name,
            'pet_level'  => $pet->pet_level,
            'pet_swf'    => $pet->pet_swf,
            'pet_skills' => $pet->pet_skills,
            'pet_mp'     => $pet->pet_mp,
            'pet_xp'     => $pet->pet_xp,
        ];
    }

    private function validateSession($userId, $sessionKey)
    {
        $user = User::find($userId);

        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }
}
