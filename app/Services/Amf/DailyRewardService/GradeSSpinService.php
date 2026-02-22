<?php

namespace App\Services\Amf\DailyRewardService;

use App\Models\Character;
use App\Models\CharacterMissionSpin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GradeSSpinService
{
    public function getMissionGradeSSpin($charId, $sessionKey)
    {
        try {
             $spin = CharacterMissionSpin::where('character_id', $charId)->first();
             $spinsAvailable = $spin ? $spin->spins_available : 0;

             $rewards = [
                    (object)['id' => 'gold_5000', 'qty' => 5000],
                    (object)['id' => 'xp_2000', 'qty' => 2000],
                    (object)['id' => 'tokens_10', 'qty' => 10],
                    (object)['id' => 'tp_20', 'qty' => 20],
                    (object)['id' => 'material_02', 'qty' => 1],
                    (object)['id' => 'essential_05', 'qty' => 1],
             ];

             return (object)[
                 'status' => 1,
                 'rewards' => $rewards,
                 'spin' => (object)[
                     'chance' => $spinsAvailable,
                     'max' => $spinsAvailable
                 ]
             ];
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.GradeSSpinService.getMissionGradeSSpin: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getRewardMissionGradeS($charId, $sessionKey)
    {
        return $this->processGradeSSpin($charId);
    }

    private function processGradeSSpin($charId)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $spin = CharacterMissionSpin::lockForUpdate()->where('character_id', $charId)->first();
                
                if (!$spin || $spin->spins_available < 1) {
                    return (object)['status' => 2, 'result' => 'No spins available! Finish Grade S missions first.'];
                }

                $char = Character::lockForUpdate()->find($charId);
                $user = User::lockForUpdate()->find($char->user_id);

                $spin->spins_available -= 1;
                $spin->save();

                $rewards = [
                    ['type' => 'gold', 'amount' => 5000, 'string' => 'gold_5000'],
                    ['type' => 'xp', 'amount' => 2000, 'string' => 'xp_2000'],
                    ['type' => 'tokens', 'amount' => 10, 'string' => 'tokens_10'],
                    ['type' => 'tp', 'amount' => 20, 'string' => 'tp_20'],
                    ['type' => 'item', 'item_id' => 'material_02', 'string' => 'material_02'], 
                    ['type' => 'item', 'item_id' => 'essential_05', 'string' => 'essential_05'], 
                ];

                $reward = $rewards[array_rand($rewards)];
                
                RewardHelper::applyRewardString($char, $reward['string']);

                return (object)[
                    'status' => 1,
                    'reward' => $reward['string'] . ':' . ($reward['amount'] ?? 1), 
                    'gold' => $char->gold,
                    'tokens' => $user->tokens,
                    'xp' => $char->xp,
                    'spins_left' => $spin->spins_available
                ];
            });

        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.GradeSSpinService.processGradeSSpin: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
