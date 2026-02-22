<?php

namespace App\Services\Amf\BattleSystemService;

use App\Models\Character;
use App\Helpers\ExperienceHelper;
use App\Helpers\GameDataHelper;
use App\Helpers\ItemHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MissionService
{
    private $missions = [];

    private function loadMissions() {
        if (!empty($this->missions)) return;

        $json = GameDataHelper::get_missions();
        if (is_array($json)) {
            foreach ($json as $m) {
                $id = $m['id'];
                $this->missions[$id] = [
                    'req_lvl' => $m['level'],
                    'xp' => $m['rewards']['xp'] ?? 0,
                    'gold' => $m['rewards']['gold'] ?? 0
                ];
            }
        }
    }

    public function startMission($charId, $missionId, $enemyId, $enemyStats, $unknown, $hash, $sessionKey)
    {
        $this->loadMissions();

        $char = Character::find($charId);
        if ($char == null) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }
        

        $mission = null;
        if (isset($this->missions[$missionId])) {
            $mission = $this->missions[$missionId];
        }

        if ($mission == null) {
            return (object)['status' => 0, 'error' => 'Mission data not found'];
        }

        if ($char->level < $mission['req_lvl']) {
            return (object)['status' => 0, 'error' => 'Level too low'];
        }

        // Energy Logic for Grade S
        $energyCost = 0;
        $gradeSMissions = [
            'msn_112' => 10,
            'msn_113' => 12,
            'msn_114' => 14,
            'msn_115' => 16,
            'msn_116' => 25
        ];

        if (array_key_exists($missionId, $gradeSMissions)) {
            $energyCost = $gradeSMissions[$missionId];
            
            $userEnergy = \App\Models\UserEnergy::firstOrCreate(
                ['user_id' => $char->user_id],
                ['energy_grade_s' => 100, 'max_energy_grade_s' => 100]
            );

            if ($userEnergy->energy_grade_s < $energyCost) {
                return (object)['status' => 0, 'error' => 'Not enough energy'];
            }

            $userEnergy->energy_grade_s -= $energyCost;
            $userEnergy->save();
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
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $goldReward = 20;
        $xpReward = 20;

        if ($cachedBattle != null) {
            $goldReward = $cachedBattle['reward_gold'];
            $xpReward = $cachedBattle['reward_xp'];
        }

        $tpReward = 0;
        $tpRewards30 = ['msn_105', 'msn_107', 'msn_104', 'msn_102'];
        $tpRewards20 = ['msn_103', 'msn_106'];

        if (in_array($missionId, $tpRewards30)) {
            $tpReward = 30;
        } elseif (in_array($missionId, $tpRewards20)) {
            $tpReward = 20;
        }

        if ($tpReward > 0) {
            $char->tp = ($char->tp ?? 0) + $tpReward;
        }

        $char->gold = $char->gold + $goldReward;
        $char->xp = $char->xp + $xpReward;

        // Use ExperienceHelper for level-up logic
        $levelUp = ExperienceHelper::checkCharacterLevelUp($char);
        $char->save();

        Cache::forget("battle_token_" . $charId);

        // Pet XP Logic (20% of character XP)
        if ($char->equipped_pet_id) {
            ExperienceHelper::addEquippedPetXp($charId, floor($xpReward * 0.20));
        }

        // Grade S Spin Logic
        $gradeSMissions = ['msn_112', 'msn_113', 'msn_114', 'msn_115', 'msn_116'];
        if (in_array($missionId, $gradeSMissions)) {
            $spin = \App\Models\CharacterMissionSpin::firstOrCreate(
                ['character_id' => $charId],
                ['spins_available' => 0]
            );
            $spin->spins_available += 1;
            $spin->save();
        }

        $rewards = [];
        if ($tpReward > 0) {
            $rewards[] = "tp_" . $tpReward;
        }

        // Mission specific material rewards
        if ($missionId == 'msn_101') {
            ItemHelper::addItem($charId, 'material_874');
            ItemHelper::addItem($charId, 'material_2110');
            $rewards[] = "material_874";
            $rewards[] = "material_2110";
        }

        return (object)[
            'status' => 1,
            'error' => 0,
            'result' => [
                $xpReward,
                $goldReward,
                $rewards
            ],
            'level' => $char->level,
            'xp' => $char->xp,
            'level_up' => $levelUp,
            'account_tokens' => 0
        ];
    }
}
