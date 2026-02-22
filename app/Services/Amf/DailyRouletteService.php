<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterDailyRoulette;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyRouletteService
{
    private $rewards = [
        1 => ['type' => 'gold', 'amount' => 1000, 'string' => 'gold_1000'],
        2 => ['type' => 'xp', 'amount' => 500, 'string' => 'xp_500'],
        3 => ['type' => 'tokens', 'amount' => 2, 'string' => 'tokens_2'],
        4 => ['type' => 'tp', 'amount' => 5, 'string' => 'tp_5'],
        5 => ['type' => 'item', 'item_id' => 'material_874', 'string' => 'material_874'],
        6 => ['type' => 'gold', 'amount' => 5000, 'string' => 'gold_5000'],
        7 => ['type' => 'xp', 'amount' => 2000, 'string' => 'xp_2000'],
        8 => ['type' => 'tokens', 'amount' => 10, 'string' => 'tokens_10'],
        9 => ['type' => 'tp', 'amount' => 20, 'string' => 'tp_20'],
        10 => ['type' => 'item', 'item_id' => 'essential_03', 'string' => 'essential_03'],
    ];

    public function getData($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $today = now()->toDateString();
                $roulette = CharacterDailyRoulette::firstOrCreate(
                    ['character_id' => $charId],
                    [
                        'consecutive_days' => 1,
                        'last_spin_date' => null
                    ]
                );

                $lastDate = $roulette->last_spin_date ? $roulette->last_spin_date->toDateString() : null;
                
                // Update consecutive days
                if ($lastDate !== $today) {
                    $yesterday = now()->subDay()->toDateString();
                    if ($lastDate === $yesterday) {
                        $roulette->consecutive_days = min($roulette->consecutive_days + 1, 10);
                    } else if ($lastDate !== null) {
                        $roulette->consecutive_days = 1;
                    }
                    $roulette->save();
                }

                return (object)[
                    'status' => 1,
                    'bonus' => $roulette->consecutive_days,
                    'can_spin' => ($lastDate === $today) ? 0 : 1
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRoulette.getData: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function spin($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $roulette = CharacterDailyRoulette::where('character_id', $charId)->lockForUpdate()->first();
                $today = now()->toDateString();

                if ($roulette && $roulette->last_spin_date && $roulette->last_spin_date->toDateString() === $today) {
                    return (object)['status' => 2, 'result' => 'You Can Spin Again Tomorrow!'];
                }

                if (!$roulette) {
                    $roulette = CharacterDailyRoulette::create([
                        'character_id' => $charId,
                        'consecutive_days' => 1,
                        'last_spin_date' => $today
                    ]);
                } else {
                    $roulette->last_spin_date = $today;
                    $roulette->save();
                }

                // Random reward index 1-10
                $rewardIdx = rand(1, 10);
                $reward = $this->rewards[$rewardIdx];

                $this->applyReward($char, $reward);

                $user = User::find($char->user_id);

                return (object)[
                    'status' => 1,
                    'reward' => $rewardIdx,
                    'reward_string' => $reward['string'],
                    'bonus' => $roulette->consecutive_days,
                    'gold' => $char->gold,
                    'tokens' => $user->tokens ?? 0,
                    'xp' => $char->xp,
                    'level_up' => false // Simplified for now
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRoulette.spin: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    private function applyReward($char, $reward)
    {
        \App\Helpers\ItemHelper::addItem($char->id, $reward['string'], 1);
    }


}
