<?php

namespace App\Services\Amf\MysteriousMarketService;

use App\Models\Character;
use App\Models\User;
use App\Models\CharacterLimitedStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshPackageService
{
    use MarketHelperTrait;

    public function refreshPackage($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                $store = CharacterLimitedStore::lockForUpdate()->where('character_id', $charId)->first();

                if (!$store) {
                     return (object)['status' => 2, 'result' => 'Store not initialized'];
                }
                
                $cost = 100;
                
                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens to refresh!'];
                }

                $user->tokens -= $cost;
                $user->save();

                $randomItems = $this->generateRandomItems();
                $discount = rand(1, 5) * 10;
                
                $store->items = $randomItems;
                $store->discount = $discount;
                $store->refresh_count += 1;
                $store->save();

                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
             Log::error("MysteriousMarket.refreshPackage error: " . $e->getMessage());
             return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
