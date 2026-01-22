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
        1 => ['type' => 'gold', 'amount' => 1000, 'string' => 'gold_~1000'],
        2 => ['type' => 'xp', 'amount' => 500, 'string' => 'xp_~500'],
        3 => ['type' => 'tokens', 'amount' => 2, 'string' => 'tokens_~2'],
        4 => ['type' => 'tp', 'amount' => 5, 'string' => 'tp_~5'],
        5 => ['type' => 'item', 'item_id' => 'material_874', 'string' => 'material_874'],
        6 => ['type' => 'gold', 'amount' => 5000, 'string' => 'gold_~5000'],
        7 => ['type' => 'xp', 'amount' => 2000, 'string' => 'xp_~2000'],
        8 => ['type' => 'tokens', 'amount' => 10, 'string' => 'tokens_~10'],
        9 => ['type' => 'tp', 'amount' => 20, 'string' => 'tp_~20'],
        10 => ['type' => 'item', 'item_id' => 'essential_03', 'string' => 'essential_03'],
    ];

    public function getData($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

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

                return [
                    'status' => 1,
                    'bonus' => $roulette->consecutive_days,
                    'can_spin' => ($lastDate === $today) ? 0 : 1
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRoulette.getData: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function spin($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $roulette = CharacterDailyRoulette::where('character_id', $charId)->lockForUpdate()->first();
                $today = now()->toDateString();

                if ($roulette && $roulette->last_spin_date && $roulette->last_spin_date->toDateString() === $today) {
                    return ['status' => 2, 'result' => 'You Can Spin Again Tomorrow!'];
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

                return [
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
            return ['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    private function applyReward($char, $reward)
    {
        switch ($reward['type']) {
            case 'gold':
                $char->gold += $reward['amount'];
                $char->save();
                break;
            case 'xp':
                $char->xp += $reward['amount'];
                // Check level up logic if needed
                $char->save();
                break;
            case 'tokens':
                $user = User::find($char->user_id);
                if ($user) {
                    $user->tokens += $reward['amount'];
                    $user->save();
                }
                break;
            case 'tp':
                $char->tp += $reward['amount'];
                $char->save();
                break;
            case 'item':
                $this->addItem($char->id, $reward['item_id']);
                break;
        }
    }

    private function addItem($charId, $itemId)
    {
        $category = 'item';
        if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
        elseif (str_starts_with($itemId, 'back_')) $category = 'back';
        elseif (str_starts_with($itemId, 'set_')) $category = 'set';
        elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
        elseif (str_starts_with($itemId, 'material_')) {
            $category = 'material';
        }
        elseif (str_starts_with($itemId, 'essential_')) {
            $category = 'essential';
        }
        elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';

        $item = CharacterItem::where('character_id', $charId)
            ->where('item_id', $itemId)
            ->first();

        if ($item) {
            $item->quantity += 1;
            $item->save();
        } else {
            CharacterItem::create([
                'character_id' => $charId,
                'item_id' => $itemId,
                'quantity' => 1,
                'category' => $category
            ]);
        }
    }
}
