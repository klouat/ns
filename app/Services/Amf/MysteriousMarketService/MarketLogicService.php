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

class MarketLogicService
{
    private $refreshCostBase = 100;

    public function getPackageData($charId, $sessionKey)
    {
        try {
            $charLimitedStore = CharacterLimitedStore::where('character_id', $charId)->first();
            
            $now = Carbon::now();

            if (!$charLimitedStore || $charLimitedStore->end_time <= $now) {
                
                $randomItems = $this->generateRandomItems();
                
                $endTime = $now->copy()->addHours(24);
                
                $discount = rand(1, 5) * 10;

                if ($charLimitedStore) {
                    $charLimitedStore->update([
                        'items' => $randomItems,
                        'end_time' => $endTime,
                        'discount' => $discount,
                        'refresh_count' => 0 
                    ]);
                } else {
                    $charLimitedStore = CharacterLimitedStore::create([
                        'character_id' => $charId,
                        'items' => $randomItems,
                        'end_time' => $endTime,
                        'discount' => $discount,
                        'refresh_count' => 0
                    ]);
                }
            }

            $refreshCost = 100;
            
            $formattedItems = [];
            $itemIds = $charLimitedStore->items ?? []; 
            
            foreach ($itemIds as $itemId) {
                $dbItem = LimitedStoreItem::where('item_id', $itemId)->first();
                if ($dbItem) {
                    $discountPercent = $charLimitedStore->discount;
                    $emblemPrice = (int)floor($dbItem->price_token * (1 - ($discountPercent / 100)));
                    if ($emblemPrice < 0) $emblemPrice = 0;

                    $formattedItems[] = (object)[
                        'code' => $itemId,
                        'prices' => [
                            $dbItem->price_token, 
                            $emblemPrice 
                        ]
                    ];
                }
            }

            $secondsRemaining = $now->diffInSeconds($charLimitedStore->end_time, false);
            if ($secondsRemaining < 0) $secondsRemaining = 0;

            return (object)[
                'status' => 1,
                'end_time' => (int)$secondsRemaining,
                'discounts' => (string)$charLimitedStore->discount,
                'refresh_cost' => $refreshCost,
                'refresh_count' => $charLimitedStore->refresh_count,
                'items' => $formattedItems
            ];

        } catch (\Exception $e) {
            Log::error("MysteriousMarket.getPackageData error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

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

    public function getAllPackagesList($charId, $sessionKey)
    {
        try {
            $items = LimitedStoreItem::where('is_active', true)->get();
            
            $filteredItems = $items->groupBy(function($item) {
                return $item->group_id ?? ('single_' . $item->id);
            })->map(function($group) {
                return $group->sortByDesc('sort_order')->first();
            });

            $ownedSkills = CharacterSkill::where('character_id', $charId)->pluck('skill_id')->toArray();

            $packages = [];
            foreach ($filteredItems as $item) {
                $packages[] = (object)[
                    'advanced_skill' => $item->item_id,
                    'owned' => in_array($item->item_id, $ownedSkills)
                ];
            }

            return (object)[
                'status' => 1,
                'packages' => $packages
            ];
        } catch (\Exception $e) {
            Log::error("MysteriousMarket.getAllPackagesList error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

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

    private function hasSkill($charId, $skillId) {
        return CharacterSkill::where('character_id', $charId)->where('skill_id', $skillId)->exists();
    }

    private function generateRandomItems() {
        $allItems = LimitedStoreItem::where('is_active', true)->get();
        $groupedItems = $allItems->groupBy(function ($item) {
            return $item->group_id ?? ('single_' . $item->id);
        });

        if ($groupedItems->isEmpty()) {
            return [];
        }

        $randomGroup = $groupedItems->random();
        $sortedGroup = $randomGroup->sortBy('sort_order');

        return $sortedGroup->pluck('item_id')->toArray();
    }
}
