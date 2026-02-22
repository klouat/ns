<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValentineEvent2026Service
{
    public function getPackage($charId, $sessionKey)
    {
        // Return active status. 
        // We can add logic to check if user already bought the package if needed.
        // The client code expects 'package_bought' boolean.
        
        $char = Character::find($charId);
        // Default to not bought for now, or check a flag if implemented
        // Logic to check if user already bought the package would go here.
        // For example checking a database record or JSON field.
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

                // IMPORTANT: 
                // Since the event configuration (prices, items) is currently client-side (GameData),
                // we'll proceed with a successful response to allow the client to update visually.
                // Ideally, you should implement token deduction and item addition here 
                // once the server-side configuration is available.

                // Example deduction logic (commented out):
                /*
                $price = ($type == 'package') ? 1000 : 500; // Example prices
                if ($user->tokens < $price) {
                    return (object)['status' => 0, 'result' => 'Not enough tokens!'];
                }
                $user->tokens -= $price;
                $user->save();
                */
                
                // Allow purchase for now
                return (object)['status' => 1];
            });

        } catch (\Exception $e) {
            Log::error("ValentineEvent2026.buyItem error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
