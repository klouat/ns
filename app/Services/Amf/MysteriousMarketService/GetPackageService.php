<?php

namespace App\Services\Amf\MysteriousMarketService;

use App\Models\CharacterLimitedStore;
use App\Models\LimitedStoreItem;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GetPackageService
{
    use MarketHelperTrait;

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
}
