<?php

namespace App\Services\Amf\EudemonGardenService;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class DataService
{
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

        return (object)[
            'status' => 1,
            'data' => implode(',', $attempts)
        ];
    }
}
