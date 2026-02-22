<?php

namespace App\Services\Amf\TalentService;

use App\Models\Character;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuyTPService
{
    public function buyPackageTP($charId, $sessionKey, $packageId)
    {
        try {
            return DB::transaction(function () use ($charId, $packageId) {
                $costs = [20, 100, 200, 400];
                $rewards = [20, 125, 250, 600];

                if (!isset($costs[$packageId])) {
                     return (object)['status' => 2, 'result' => 'Invalid package ID'];
                }

                $cost = $costs[$packageId];
                $reward = $rewards[$packageId];

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = \App\Models\User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }

                $user->tokens -= $cost;
                $user->save();

                $char->tp += $reward;
                $char->save();

                return (object)[
                    'status' => 1,
                    'price' => $cost,
                    'add' => $reward,
                    'current_tp' => $char->tp,
                    'current_tokens' => $user->tokens
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
