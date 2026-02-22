<?php

namespace App\Services\Amf\ValentineEvent2026Service;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValentineService
{
    public function getPackage($charId, $sessionKey)
    {
        $char = Character::find($charId);
        $bought = false; 
        
        return (object)[
            'status' => 1,
            'package_bought' => $bought
        ];
    }

    public function buyItem($charId, $sessionKey, $type)
    {
        try {
            return DB::transaction(function () use ($charId, $type) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                return (object)['status' => 1];
            });

        } catch (\Exception $e) {
            Log::error("ValentineEvent2026.buyItem error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
