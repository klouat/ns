<?php

namespace App\Services\Amf;

use App\Models\Character;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BattleSystemService
{
    private $missions = [
        'msn_1' => ['req_lvl' => 1, 'xp' => 20, 'gold' => 20],
        'msn_2' => ['req_lvl' => 2, 'xp' => 40, 'gold' => 40],
        'msn_3' => ['req_lvl' => 3, 'xp' => 60, 'gold' => 60],
        'msn_4' => ['req_lvl' => 4, 'xp' => 80, 'gold' => 80],
        'msn_5' => ['req_lvl' => 5, 'xp' => 100, 'gold' => 100],
        'msn_6' => ['req_lvl' => 6, 'xp' => 120, 'gold' => 120],
        'msn_7' => ['req_lvl' => 7, 'xp' => 140, 'gold' => 140],
        'msn_8' => ['req_lvl' => 8, 'xp' => 180, 'gold' => 160],
        'msn_9' => ['req_lvl' => 9, 'xp' => 200, 'gold' => 180],
        'msn_10' => ['req_lvl' => 10, 'xp' => 240, 'gold' => 200],
        'msn_11' => ['req_lvl' => 11, 'xp' => 300, 'gold' => 220],
        'msn_12' => ['req_lvl' => 12, 'xp' => 340, 'gold' => 220],
        'msn_13' => ['req_lvl' => 13, 'xp' => 380, 'gold' => 270],
        'msn_14' => ['req_lvl' => 14, 'xp' => 420, 'gold' => 300],
        'msn_15' => ['req_lvl' => 15, 'xp' => 460, 'gold' => 330],
        'msn_16' => ['req_lvl' => 16, 'xp' => 500, 'gold' => 360],
        'msn_17' => ['req_lvl' => 17, 'xp' => 540, 'gold' => 400],
        'msn_18' => ['req_lvl' => 18, 'xp' => 580, 'gold' => 440],
        'msn_19' => ['req_lvl' => 19, 'xp' => 620, 'gold' => 475],
        'msn_20' => ['req_lvl' => 20, 'xp' => 660, 'gold' => 515],
    ];

    private $xpTable = [
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

    public function startMission($charId, $missionId, $enemyId, $enemyStats, $unknown, $hash, $sessionKey)
    {
        $char = Character::find($charId);
        if ($char == null) {
            return ['status' => 0, 'error' => 'Character not found'];
        }
        

        $mission = null;
        if (isset($this->missions[$missionId])) {
            $mission = $this->missions[$missionId];
        }

        if ($mission == null) {
            return ['status' => 0, 'error' => 'Mission data not found'];
        }

        if ($char->level < $mission['req_lvl']) {
            return ['status' => 0, 'error' => 'Level too low'];
        }

        $token = Str::random(10);

        Cache::put("battle_token_" . $charId, [
            'token' => $token,
            'mission_id' => $missionId,
            'reward_xp' => $mission['xp'],
            'reward_gold' => $mission['gold']
        ], 1800);

        return $token;
    }

    public function finishMission($charId, $missionId, $token, $hash, $score, $sessionKey, $battleData, $unknown)
    {
        $cachedBattle = Cache::get("battle_token_" . $charId);

        $char = Character::find($charId);
        if ($char == null) {
            return ['status' => 0, 'error' => 'Character not found'];
        }

        $goldReward = 20;
        $xpReward = 20;

        if ($cachedBattle != null) {
            $goldReward = $cachedBattle['reward_gold'];
            $xpReward = $cachedBattle['reward_xp'];
        }

        $char->gold = $char->gold + $goldReward;
        $char->xp = $char->xp + $xpReward;

        $levelUp = false;

        while ($char->level < 85) {
            $required = 999999999;
            if (isset($this->xpTable[$char->level])) {
                $required = $this->xpTable[$char->level];
            }

            if ($char->xp >= $required) {
                $char->level = $char->level + 1;
                $levelUp = true;
            } else {
                break;
            }
        }

        $char->save();

        Cache::forget("battle_token_" . $charId);

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
                 
                 while (isset($petXpTable[$pet->pet_level])) {
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

        return [
            'status' => 1,
            'error' => 0,
            'result' => [
                $goldReward,
                $xpReward,
                []
            ],
            'level' => $char->level,
            'xp' => $char->xp,
            'level_up' => $levelUp,
            'account_tokens' => 0
        ];
    }
}
