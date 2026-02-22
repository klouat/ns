<?php

namespace App\Services\Amf\DragonHuntEventService;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MaterialService
{
    public function buyMaterial($charId, $sessionKey, $materialId, $amount)
    {
        return DB::transaction(function () use ($charId, $materialId, $amount) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);
            
            if (!$char || !$user) {
                return (object)['status' => 0, 'error' => 'Character or user not found'];
            }

            $pricePerItem = 10;
            $totalCost = $pricePerItem * $amount;

            if ($user->tokens < $totalCost) {
                return (object)['status' => 2, 'result' => 'Not enough tokens'];
            }

            $user->tokens -= $totalCost;
            $user->save();

            $item = CharacterItem::firstOrCreate(
                ['character_id' => $charId, 'item_id' => $materialId],
                ['quantity' => 0, 'category' => 'item']
            );
            
            $item->quantity += $amount;
            $item->save();

            return (object)[
                'status' => 1,
                'tokens' => $user->tokens
            ];
        });
    }
}
