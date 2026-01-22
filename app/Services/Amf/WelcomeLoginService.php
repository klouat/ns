<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterWelcomeLogin;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WelcomeLoginService
{
    private $rewardsList = [
        ['day' => 1, 'r' => 'gold:10000'],
        ['day' => 2, 'r' => 'token:50'],
        ['day' => 3, 'r' => 'xp:5000'],
        ['day' => 4, 'r' => 'token:100'],
        ['day' => 5, 'r' => 'xp:10000'],
        ['day' => 6, 'r' => 'token:200'],
        ['day' => 7, 'r' => 'gold:100000'],
    ];

    public function get($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $today = now()->toDateString();
                $welcome = CharacterWelcomeLogin::firstOrCreate(
                    ['character_id' => $charId],
                    [
                        'login_count' => 1,
                        'last_login_date' => $today,
                        'claimed_days' => []
                    ]
                );

                $lastDate = $welcome->last_login_date ? $welcome->last_login_date->toDateString() : null;

                if ($lastDate !== $today) {
                    $welcome->login_count = min($welcome->login_count + 1, 7);
                    $welcome->last_login_date = $today;
                    $welcome->save();
                }

                $claimed = $welcome->claimed_days ?? [];
                $rewards = [];
                foreach ($this->rewardsList as $index => $reward) {
                    $rewards[] = [
                        'day' => "Day " . ($index + 1),
                        'id' => (int)$reward['day'],
                        'r' => (string)$reward['r'],
                        'c' => (int)(in_array($index, $claimed) ? 1 : 0)
                    ];
                }

                return [
                    'status' => 1,
                    'logins' => (int)$welcome->login_count,
                    'rewards' => $rewards,
                    'days' => (int)$welcome->login_count
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in WelcomeLogin.get: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claim($charId, $sessionKey, $dayIdx)
    {
        try {
            return DB::transaction(function () use ($charId, $dayIdx) {
                $char = Character::lockForUpdate()->find($charId);
                $welcome = CharacterWelcomeLogin::where('character_id', $charId)->lockForUpdate()->first();

                if (!$welcome) return ['status' => 0, 'error' => 'Data not found'];

                if ($dayIdx >= $welcome->login_count) {
                    return ['status' => 2, 'result' => 'Day not reached yet!'];
                }

                $claimed = $welcome->claimed_days ?? [];
                if (in_array($dayIdx, $claimed)) {
                    return ['status' => 2, 'result' => 'Already claimed!'];
                }

                $rewardStr = $this->rewardsList[$dayIdx]['r'];
                $claimed[] = (int)$dayIdx;
                $welcome->claimed_days = $claimed;
                $welcome->save();

                $formattedReward = $this->applyReward($char, $rewardStr);

                return [
                    'status' => 1,
                    'rewards' => [$formattedReward]
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in WelcomeLogin.claim: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    private function applyReward($char, $rewardStr)
    {
        $parts = explode(':', $rewardStr);
        $type = $parts[0];
        $qty = isset($parts[1]) ? (int)$parts[1] : 1;

        $formatted = "";

        if ($type === 'gold' || $type === 'item_gold') {
            $char->gold += $qty;
            $char->save();
            $formatted = "gold_~" . $qty;
        } elseif ($type === 'tokens' || $type === 'token') {
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $qty;
                $user->save();
            }
            $formatted = "tokens_~" . $qty;
        } elseif ($type === 'xp') {
            $char->xp += $qty;
            $char->save();
            $formatted = "xp_~" . $qty;
        } elseif ($type === 'tp') {
            $char->tp += $qty;
            $char->save();
            $formatted = "tp_~" . $qty;
        } else {
            // Assume item
            $this->addItem($char->id, $type, $qty);
            $formatted = $type;
        }

        return $formatted;
    }

    private function addItem($charId, $itemId, $qty)
    {
        $category = 'item';
        if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
        elseif (str_starts_with($itemId, 'back_')) $category = 'back';
        elseif (str_starts_with($itemId, 'set_')) $category = 'set';
        elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
        elseif (str_starts_with($itemId, 'material_')) $category = 'material';
        elseif (str_starts_with($itemId, 'essential_')) $category = 'essential';
        elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';

        $item = CharacterItem::where('character_id', $charId)
            ->where('item_id', $itemId)
            ->first();

        if ($item) {
            $item->quantity += $qty;
            $item->save();
        } else {
            CharacterItem::create([
                'character_id' => $charId,
                'item_id' => $itemId,
                'quantity' => $qty,
                'category' => $category
            ]);
        }
    }
}
