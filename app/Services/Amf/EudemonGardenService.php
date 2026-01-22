<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

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

        return [
            'status' => 1,
            'data' => implode(',', $attempts)
        ];
    }

    public function buyTries($sessionKey, $charId)
    {
        $char = Character::find($charId);
        if (!$char) return ['status' => 0, 'error' => 'Character not found'];
        
        $user = User::find($char->user_id);
        if (!$user) return ['status' => 0, 'error' => 'User not found'];

        $cost = ($char->level >= 80) ? 80 : 50;

        if ($user->tokens < $cost) {
            return ['status' => 2];
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

        return [
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
             return ['status' => 2, 'result' => 'No attempts left!'];
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

        return [
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
            'rewards' => ['wpn_1138', 'material_01', 'material_2110', 'item_58'],
            'gold' => 30000,
            'xp' => 20000
        ],
        1 => [
            'id' => ['ene_461'],
            'name' => 'Hell Horse',
            'lvl' => 20,
            'rewards' => ['wpn_1140', 'material_01', 'material_02', 'material_2110', 'item_58'],
            'gold' => 35000,
            'xp' => 23000
        ],
        2 => [
            'id' => ['ene_462'],
            'name' => 'Kabutomushi Musha',
            'lvl' => 25,
            'rewards' => ['wpn_1142', 'material_01', 'material_02', 'material_2110', 'item_58'],
            'gold' => 38000,
            'xp' => 25000
        ],
        3 => [
            'id' => ['ene_463', 'ene_464'],
            'name' => 'Kinkaku & Ginkaku',
            'lvl' => 30,
            'rewards' => ['wpn_1144', 'wpn_1146', 'material_01', 'material_02', 'material_03', 'material_2110', 'item_58'],
            'gold' => 40000,
            'xp' => 30000
        ],
        4 => [
            'id' => ['ene_465'],
            'name' => 'Thunder Eagle',
            'lvl' => 40,
            'rewards' => ['wpn_1148', 'material_01', 'material_02', 'material_03', 'material_2110', 'item_58'],
            'gold' => 49000,
            'xp' => 35000
        ],
        5 => [
            'id' => ['ene_466'],
            'name' => 'Mammoth King',
            'lvl' => 50,
            'rewards' => ['wpn_1150', 'material_01', 'material_02', 'material_03', 'material_04', 'material_2110', 'item_58'],
            'gold' => 55000,
            'xp' => 38000
        ],
        6 => [
            'id' => ['ene_467'],
            'name' => 'Ocean Queen',
            'lvl' => 55,
            'rewards' => ['wpn_1152', 'material_01', 'material_02', 'material_03', 'material_04', 'material_2110', 'item_58'],
            'gold' => 57000,
            'xp' => 40000
        ],
        7 => [
            'id' => ['ene_468'],
            'name' => 'Ghost Soldier',
            'lvl' => 60,
            'rewards' => ['wpn_1154', 'material_02', 'material_03', 'material_04', 'material_05', 'material_2110', 'item_58'],
            'gold' => 62000,
            'xp' => 48000
        ],
        8 => [
            'id' => ['ene_469'],
            'name' => 'Battle Angel',
            'lvl' => 70,
            'rewards' => ['wpn_1156', 'material_02', 'material_03', 'material_04', 'material_05', 'material_2110', 'item_58'],
            'gold' => 72000,
            'xp' => 56000
        ],
        9 => [
            'id' => ['ene_470'],
            'name' => 'Infernal Chimera',
            'lvl' => 80,
            'rewards' => ['wpn_1158', 'material_01', 'material_02', 'material_03', 'material_04', 'material_05', 'material_06', 'material_2110', 'item_58'],
            'gold' => 100000,
            'xp' => 75000
        ],
        10 => [
            'id' => ['ene_432', 'ene_433'],
            'name' => 'Taowu & Taotie',
            'lvl' => 90,
            'rewards' => ['wpn_1111', 'wpn_1112', 'material_01', 'material_02', 'material_03', 'material_04', 'material_05', 'material_06', 'material_2110', 'item_58'],
            'gold' => 150000,
            'xp' => 100000
        ]
    ];

    public function finishHunting($charId, $bossNum, $code, $hash, $sessionKey, $battleData)
    {
        // Simple hash validation or skip for now
        
        $char = Character::find($charId);
        if (!$char) {
            return ['status' => 0, 'error' => 'Character not found'];
        }

        // Default to fallback if boss num not found
        $xpReward = 100;
        $goldReward = 100;
        $itemsRewarded = [];

        if (array_key_exists($bossNum, $this->bossData)) {
            $boss = $this->bossData[$bossNum];
            $xpReward = $boss['xp'];
            $goldReward = $boss['gold'];
            
            // Item drop logic:
            // 20% chance to get an item from the list
            if (rand(1, 100) <= 20 && !empty($boss['rewards'])) {
                 $randomItem = $boss['rewards'][array_rand($boss['rewards'])];
                 $itemsRewarded[] = $randomItem;
                 
                 // TODO: Actually add item to user inventory here
            }
        }
        
        // Add rewards
        $char->gold += $goldReward;
        $char->xp += $xpReward;

        // Pet XP Logic
        if ($char->equipped_pet_id) {
            $pet = \App\Models\CharacterPet::find($char->equipped_pet_id);
            if ($pet) {
                 $petXpGain = floor($xpReward * 0.20);
                 $pet->pet_xp += $petXpGain;
                 
                 // Pet Level Up Logic (Precise Table)
                 $petXpTable = [
                    1 => 28, 2 => 61, 3 => 99, 4 => 142, 5 => 192, 6 => 249, 7 => 315, 8 => 389, 9 => 473, 10 => 569,
                    11 => 676, 12 => 798, 13 => 935, 14 => 1088, 15 => 1261, 16 => 1455, 17 => 1671, 18 => 1914, 19 => 2184, 20 => 2487,
                    21 => 2823, 22 => 3198, 23 => 3616, 24 => 4080, 25 => 4596, 26 => 5196, 27 => 5805, 28 => 6510, 29 => 7291, 30 => 8156,
                    31 => 9114, 32 => 10173, 33 => 11345, 34 => 12640, 35 => 14071, 36 => 15651, 37 => 17395, 38 => 19319, 39 => 21440, 40 => 23780,
                    41 => 27733, 42 => 30696, 43 => 33471, 44 => 36193, 45 => 39579, 46 => 42140, 47 => 46342, 48 => 49634, 49 => 53379, 50 => 56695,
                    51 => 59936, 52 => 66622, 53 => 70841, 54 => 74605, 55 => 79734, 56 => 86755, 57 => 90227, 58 => 95427, 59 => 103740, 60 => 110291,
                    61 => 125307, 62 => 145705, 63 => 174070, 64 => 211985, 65 => 259748, 66 => 314393, 67 => 377280, 68 => 447571, 69 => 526381, 70 => 612222,
                    71 => 705963, 72 => 806478, 73 => 912730, 74 => 1026380, 75 => 1144886, 76 => 1269847, 77 => 1402425, 78 => 1538415, 79 => 1683103, 80 => 1831845,
                    81 => 2049957, 82 => 2372858, 83 => 2695758, 84 => 3018659, 85 => 3541560, 86 => 4057490, 87 => 4639279, 88 => 5221067, 89 => 5902856, 90 => 6184644,
                    91 => 7297879, 92 => 8611498, 93 => 10161568, 94 => 11990650, 95 => 14148967, 96 => 65910291, 97 => 81728761, 98 => 101343664, 99 => 125666144, 100 => 155826018
                 ];
                 
                 // While next level exists in table and we have enough XP
                 while (isset($petXpTable[$pet->pet_level])) {
                     // The table key is the level, value is XP required to REACH NEXT level?
                     // Or is it accumulated XP required to BE at that level?
                     // Standard NS tables usually map Level => XP Required for next level OR Total XP at that level.
                     // The user provided "1: 28". If pet is level 1, needs 28 XP to get to level 2?
                     // Or is 28 the XP at level 1? Usually level 1 starts at 0.
                     // Let's assume Key = current level, Value = Total XP required to reach Next Level (Key+1).
                     // So at Level 1, if XP >= 28, become Level 2.
                     
                     $reqXp = $petXpTable[$pet->pet_level];
                     if ($pet->pet_xp >= $reqXp) {
                         $pet->pet_level++;
                     } else {
                         break;
                     }
                 }
                 $pet->save();
            }
        }

        // Level up logic (simplified)
        $levelUp = false;

        // Basic XP table check could be added here or extracted to a service
        // For now, let's just save. Real implementation should check XP table.
        // Copying simplified logic from BattleSystemService
        $xpTable = [
            1 => 15, 2 => 304, 3 => 493, 4 => 711, 5 => 961, 6 => 1247, 7 => 1574, 8 => 1945, 9 => 2366, 10 => 2843,
            11 => 3382, 12 => 3989, 13 => 4673, 14 => 5542, 15 => 6306, 16 => 7273, 17 => 8537, 18 => 9569, 19 => 10922, 20 => 12433,
            21 => 14117, 22 => 15992, 23 => 18080, 24 => 20401, 25 => 22981, 26 => 25845, 27 => 29024, 28 => 32548, 29 => 36454, 30 => 40780,
            31 => 45569, 32 => 50867, 33 => 56725, 34 => 63201, 35 => 70354, 36 => 78254, 37 => 86973, 38 => 96593, 39 => 107202, 40 => 118899,
            41 => 131790, 42 => 145991, 43 => 161632, 44 => 178850, 45 => 197801, 46 => 218652, 47 => 241587, 48 => 266806, 49 => 294530, 50 => 325000,
            51 => 358478, 52 => 395253, 53 => 435640, 54 => 479982, 55 => 528656, 56 => 582073, 57 => 640648, 58 => 704980, 59 => 775497, 60 => 858822,
            61 => 973598, 62 => 1030523, 63 => 1132364, 64 => 1243956, 65 => 1366211, 66 => 1500266, 67 => 1646789, 68 => 1807388, 69 => 1983211, 70 => 2175702,
            71 => 3857490, 72 => 5539279, 73 => 7221067, 74 => 8902856, 75 => 10584644, 76 => 34958287, 77 => 38667739, 78 => 42377192, 79 => 46086644, 80 => 49796096,
            81 => 69149957, 82 => 73172858, 83 => 77195758, 84 => 81218659, 85 => 85241560, 86 => 118206898, 87 => 130658489, 88 => 143202210, 89 => 155837542, 90 => 168563974,
            91 => 586602629, 92 => 686931906, 93 => 811340210, 94 => 965606507, 95 => 1156896715, 96 => 4164828174, 97 => 5067207611, 98 => 6240300880, 99 => 7765322130, 100 => 9747849755
        ];
        
        while ($char->level < 100) { // Max level check
            $required = $xpTable[$char->level] ?? 999999999;
             if ($char->xp >= $required) {
                $char->level++;
                $levelUp = true;
            } else {
                break;
            }
        }

        $char->save();

        return [
            'status' => 1,
            'xp' => $char->xp,
            'level' => $char->level,
            'level_up' => $levelUp,
            'result' => [
                $goldReward,
                $xpReward,
                $itemsRewarded // Items
            ]
        ];
    }
}
