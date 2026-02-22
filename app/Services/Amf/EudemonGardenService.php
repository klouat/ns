<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\User;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EudemonGardenService
{
    // Assuming 5 bosses for now based on the fact that pagination is by 5
    // and usually these started with a small number.
    // If the client has more in its GameData, we might need to increase this.
    // The client loops through comma separated values, so providing more values than bosses 
    // is usually safer than fewer. Let's send 20 to be safe.
    private $bossCount = 20; 
    private $defaultAttempts = 3;

    public function getData($sessionKey, $charId)
    {
        $attempts = [];
        $today = date('Y-m-d');
        
        for ($i = 0; $i < $this->bossCount; $i++) {
            $key = "eudemon_tries_{$charId}_{$i}_{$today}";
            $attempts[] = Cache::get($key, $this->defaultAttempts);
        }

        return (object)[
            'status' => 1,
            'data' => implode(',', $attempts)
        ];
    }

    public function buyTries($sessionKey, $charId)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
        
        $user = User::find($char->user_id);
        if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

        $cost = ($char->level >= 80) ? 80 : 50;

        if ($user->tokens < $cost) {
            return (object)['status' => 2];
        }

        $user->tokens -= $cost;
        $user->save();

        $attempts = [];
        $today = date('Y-m-d');

        for ($i = 0; $i < $this->bossCount; $i++) {
            $key = "eudemon_tries_{$charId}_{$i}_{$today}";
            Cache::put($key, $this->defaultAttempts, now()->endOfDay());
            $attempts[] = $this->defaultAttempts;
        }

        return (object)[
            'status' => 1,
            'data' => implode(',', $attempts)
        ];
    }

    public function startHunting($charId, $bossNum, $sessionKey)
    {
        $today = date('Y-m-d');
        $key = "eudemon_tries_{$charId}_{$bossNum}_{$today}";
        $tries = Cache::get($key, $this->defaultAttempts);

        if ($tries <= 0) {
             return (object)['status' => 2, 'result' => 'No attempts left!'];
        }
        
        // Decrement try
        Cache::put($key, $tries - 1, now()->endOfDay());

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $battleCode = substr(str_shuffle($chars), 0, 32); 
        
        // Hash format from AS: 
        // var _loc2_:ByteArray = Crypto.getHash("sha256").hash(Crypto.bytesArray(param1));
        // param1 = Character.char_id + Character.battle_code + this.boss_num
        
        $hashInput = $charId . $battleCode . $bossNum;
        $hash = hash('sha256', $hashInput);

        return (object)[
            'status' => 1,
            'code' => $battleCode,
            'hash' => $hash
        ];
    }
    private $bossData = [
        0 => [
            'id' => ['ene_460'],
            'name' => 'Kamaitachi',
            'lvl' => 10,
            'rewards' => ['wpn_1138' => 5, 'material_01' => 40, 'material_2110' => 40, 'item_58' => 30],
            'gold' => 30000,
            'xp' => 20000000000
        ],
        1 => [
            'id' => ['ene_461'],
            'name' => 'Hell Horse',
            'lvl' => 20,
            'rewards' => ['wpn_1140' => 5, 'material_01' => 40, 'material_02' => 40, 'material_2110' => 30, 'item_58' => 20],
            'gold' => 35000,
            'xp' => 23000
        ],
        2 => [
            'id' => ['ene_462'],
            'name' => 'Kabutomushi Musha',
            'lvl' => 25,
            'rewards' => ['wpn_1142' => 5, 'material_01' => 40, 'material_02' => 40, 'material_2110' => 30, 'item_58' => 20],
            'gold' => 38000,
            'xp' => 25000
        ],
        3 => [
            'id' => ['ene_463', 'ene_464'],
            'name' => 'Kinkaku & Ginkaku',
            'lvl' => 30,
            'rewards' => ['wpn_1144' => 5, 'wpn_1146' => 5, 'material_01' => 40, 'material_02' => 40, 'material_03' => 30, 'material_2110' => 20, 'item_58' => 20],
            'gold' => 40000,
            'xp' => 30000
        ],
        4 => [
            'id' => ['ene_465'],
            'name' => 'Thunder Eagle',
            'lvl' => 40,
            'rewards' => ['wpn_1148' => 5, 'material_01' => 40, 'material_02' => 40, 'material_03' => 30, 'material_2110' => 20, 'item_58' => 20],
            'gold' => 49000,
            'xp' => 35000
        ],
        5 => [
            'id' => ['ene_466'],
            'name' => 'Mammoth King',
            'lvl' => 50,
            'rewards' => ['wpn_1150' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_2110' => 20, 'item_58' => 20],
            'gold' => 55000,
            'xp' => 38000
        ],
        6 => [
            'id' => ['ene_467'],
            'name' => 'Ocean Queen',
            'lvl' => 55,
            'rewards' => ['wpn_1152' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_2110' => 20, 'item_58' => 20],
            'gold' => 57000,
            'xp' => 40000
        ],
        7 => [
            'id' => ['ene_468'],
            'name' => 'Ghost Soldier',
            'lvl' => 60,
            'rewards' => ['wpn_1154' => 5, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_05' => 15, 'material_2110' => 15, 'item_58' => 15],
            'gold' => 62000,
            'xp' => 48000
        ],
        8 => [
            'id' => ['ene_469'],
            'name' => 'Battle Angel',
            'lvl' => 70,
            'rewards' => ['wpn_1156' => 5, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_05' => 15, 'material_2110' => 15, 'item_58' => 15],
            'gold' => 72000,
            'xp' => 56000
        ],
        9 => [
            'id' => ['ene_470'],
            'name' => 'Infernal Chimera',
            'lvl' => 80,
            'rewards' => ['wpn_1158' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 20, 'material_04' => 20, 'material_05' => 15, 'material_06' => 10, 'material_2110' => 15, 'item_58' => 15],
            'gold' => 100000,
            'xp' => 750000
        ],
        10 => [
            'id' => ['ene_432', 'ene_433'],
            'name' => 'Taowu & Taotie',
            'lvl' => 90,
            'rewards' => ['wpn_1111' => 2, 'wpn_1112' => 2, 'material_01' => 30, 'material_02' => 30, 'material_03' => 20, 'material_04' => 20, 'material_05' => 15, 'material_06' => 10, 'material_2110' => 15, 'item_58' => 15],
            'gold' => 150000,
            'xp' => 100000
        ]
    ];

    public function finishHunting($charId, $bossNum, $code, $hash, $sessionKey, $battleData)
    {
        return DB::transaction(function () use ($charId, $bossNum, $code, $hash, $sessionKey, $battleData) {
            
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            // Default to fallback if boss num not found
            $xpReward = 100;
            $goldReward = 100;
            $itemsRewarded = [];

            if (array_key_exists($bossNum, $this->bossData)) {
                $boss = $this->bossData[$bossNum];
                $xpReward = $boss['xp'];
                $goldReward = $boss['gold'];
                
                // Item drop logic with specific rates
                // $boss['rewards'] is now ['item_id' => percent_chance]
                if (!empty($boss['rewards'])) {
                    foreach ($boss['rewards'] as $itemId => $chance) {
                        // Roll d100
                        if (rand(1, 100) <= $chance) {
                            $itemsRewarded[] = $itemId;

                            // Add item to inventory
                            $category = 'item';
                            if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
                            elseif (str_starts_with($itemId, 'back_')) $category = 'back';
                            elseif (str_starts_with($itemId, 'set_')) $category = 'set';
                            elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
                            elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';
                            elseif (str_starts_with($itemId, 'pet_')) $category = 'pet';
                            elseif (str_starts_with($itemId, 'material_')) $category = 'material';
                            elseif (str_starts_with($itemId, 'skill_')) $category = 'skill';

                            $existingItem = \App\Models\CharacterItem::where('character_id', $charId)
                                ->where('item_id', $itemId)
                                ->first();
                            
                            if ($existingItem) {
                                $existingItem->quantity += 1;
                                $existingItem->save();
                            } else {
                                \App\Models\CharacterItem::create([
                                    'character_id' => $charId,
                                    'item_id' => $itemId,
                                    'quantity' => 1,
                                    'category' => $category
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Add rewards
            $char->gold += $goldReward;
            $char->xp += $xpReward;

            // Pet XP Logic (20% of character XP)
            if ($char->equipped_pet_id) {
                ExperienceHelper::addEquippedPetXp($charId, floor($xpReward * 0.20));
            }

            // Level up logic using ExperienceHelper
            $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
            $char->save();

            return (object)[
                'status' => 1,
                'xp' => $char->xp,
                'level' => $char->level,
                'level_up' => $levelUp,
                'result' => [
                    $xpReward,
                    $goldReward,
                    $itemsRewarded // Items
                ]
            ];
        });
    }
}
