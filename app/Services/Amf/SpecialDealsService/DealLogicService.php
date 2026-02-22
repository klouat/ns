<?php

namespace App\Services\Amf\SpecialDealsService;

use App\Models\SpecialDeal;
use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DealLogicService
{
    public function getDeals($charId, $sessionKey)
    {
        try {
            $now = Carbon::now();
            
            $activeDeals = SpecialDeal::where('is_active', true)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->get();

            $formattedDeals = [];

            foreach ($activeDeals as $deal) {
                // Determine text for "end"
                $diff = $deal->end_time->diffForHumans(['parts' => 2]);
                
                $formattedDeals[] = (object)[
                    'id' => $deal->id,
                    'name' => $deal->name,
                    'end' => "Ends in: " . $diff,
                    'price' => $deal->price,
                    'items' => array_map(function($r) {
                        $id = $r['id'] ?? ($r['item_id'] ?? '');
                        $qty = $r['q'] ?? ($r['qty'] ?? 1);
                        if ($qty > 1) {
                            return "{$id}:{$qty}";
                        }
                        return $id;
                    }, $deal->rewards ?? [])
                ];
            }
            
            return (object)[
                'status' => 1,
                'deals' => $formattedDeals
            ];

        } catch (\Exception $e) {
            Log::error("SpecialDealsService.getDeals error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function buy($charId, $sessionKey, $dealId)
    {
        try {
            return DB::transaction(function () use ($charId, $dealId) {
                $now = Carbon::now();
                
                $deal = SpecialDeal::lockForUpdate()->find($dealId);
                
                if (!$deal || !$deal->is_active || $deal->start_time > $now || $deal->end_time < $now) {
                    return (object)['status' => 2, 'result' => 'This deal has expired or does not exist.'];
                }

                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];
                
                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                if ($user->tokens < $deal->price) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens!'];
                }

                $user->tokens -= $deal->price;
                $user->save();

                $this->processRewards($char, $deal->rewards);

                return (object)[
                    'status' => 1,
                    'rewards' => array_map(function($r) { 
                        $obj = (object)$r;
                        if (!isset($obj->id) && isset($r['item_id'])) $obj->id = $r['item_id'];
                        if (!isset($obj->type)) $obj->type = 'item';
                        return $obj; 
                    }, $deal->rewards ?? [])
                ];
            });

        } catch (\Exception $e) {
            Log::error("SpecialDealsService.buy error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function processRewards($char, $rewards) {
        $charId = $char->id;
        
        foreach ($rewards as $reward) {
            $type = $reward['type'] ?? 'item';
            $qty = $reward['qty'] ?? 1;
            
            $itemId = $reward['id'] ?? ($reward['item_id'] ?? null);

            if ($type === 'item' && $itemId) {
                \App\Models\CharacterItem::create([
                    'character_id' => $charId,
                    'item_id' => $itemId,
                    'quantity' => $qty 
                ]); 
            } else if ($type === 'gold') {
                $char->gold += $qty;
                $char->save();
            } else if ($type === 'token') {
                 $user = $char->user;
                 $user->tokens += $qty;
                 $user->save();
            }
        }
    }
}
