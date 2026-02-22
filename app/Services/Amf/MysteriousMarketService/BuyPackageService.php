<?php

namespace App\Services\Amf\MysteriousMarketService;

use App\Models\Character;
use App\Models\User;
use App\Models\LimitedStoreItem;
use App\Models\CharacterLimitedStore;
use App\Models\CharacterSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BuyPackageService
{
    use MarketHelperTrait;

    public function buyPackage($charId, $sessionKey, $selectedSkillId)
    {
        try {
            return DB::transaction(function () use ($charId, $selectedSkillId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($this->hasSkill($charId, $selectedSkillId)) {
                    return (object)['status' => 2, 'result' => 'You already own this skill!'];
                }

                $store = CharacterLimitedStore::where('character_id', $charId)->first();
                
                if (!$store || $store->end_time <= Carbon::now()) {
                    return (object)['status' => 2, 'result' => 'Store expired! Please reopen the Mysterious Market.'];
                }

                if (!in_array($selectedSkillId, $store->items ?? [])) {
                    return (object)['status' => 2, 'result' => 'Item not currently in your store!'];
                }

                $item = LimitedStoreItem::where('item_id', $selectedSkillId)->first();
                if (!$item) return (object)['status' => 2, 'result' => 'Item data invalid!'];

                if ($user->account_type >= 1) { 
                    $discountPercent = $store->discount;
                    $price = (int)floor($item->price_token * (1 - ($discountPercent / 100)));
                    if ($price < 0) $price = 0;
                } else {
                    $price = $item->price_token;
                }

                if ($user->tokens < $price) {
                     return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                $user->tokens -= $price;
                $user->save();

                CharacterSkill::create([
                    'character_id' => $charId,
                    'skill_id' => $selectedSkillId
                ]);
                
                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            Log::error("MysteriousMarket.buyPackage error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
