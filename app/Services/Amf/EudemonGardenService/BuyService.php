<?php

namespace App\Services\Amf\EudemonGardenService;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class BuyService
{
    private $bossCount = 20; 
    private $defaultAttempts = 3;

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
}
