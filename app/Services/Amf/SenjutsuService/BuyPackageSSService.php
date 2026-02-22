<?php

namespace App\Services\Amf\SenjutsuService;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuyPackageSSService
{
    public function buyPackageSS($charId, $sessionKey, $packageIndex)
    {
        return DB::transaction(function () use ($charId, $packageIndex) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
            $user = User::lockForUpdate()->find($char->user_id);

            $packages = [
                0 => ['price' => 20, 'amount' => 10],
                1 => ['price' => 100, 'amount' => 55],
                2 => ['price' => 200, 'amount' => 120],
                3 => ['price' => 400, 'amount' => 250]
            ];

            if (!isset($packages[$packageIndex])) return (object)['status' => 0, 'error' => 'Invalid package'];

            $pkg = $packages[$packageIndex];
            
            if ($user->tokens < $pkg['price']) {
                return (object)['status' => 2, 'result' => 'Not enough Tokens!'];
            }

            $user->tokens -= $pkg['price'];
            $user->save();

            $char->character_ss += $pkg['amount'];
            $char->save();

            return (object)[
                'status' => 1,
                'result' => 'Sage Scroll bought successfully!',
                'ss' => $char->character_ss
            ];
        });
    }
}
