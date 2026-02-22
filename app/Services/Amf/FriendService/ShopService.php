<?php

namespace App\Services\Amf\FriendService;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;
use App\Models\FriendshipShopItem;
use Illuminate\Support\Facades\DB;

class ShopService
{
    private function shopItems()
    {
        return FriendshipShopItem::select('id', 'price', 'item')
            ->get()
            ->map(function ($item) {
                $itemStr = (string)$item->item;
                if (str_starts_with($itemStr, 'token_')) {
                    $itemStr = str_replace('token_', 'tokens_', $itemStr);
                } elseif (str_starts_with($itemStr, 'skills_')) {
                    $itemStr = str_replace('skills_', 'skill_', $itemStr);
                }
                
                return (object)[
                    'id' => (int)$item->id,
                    'price' => (int)$item->price,
                    'item' => $itemStr
                ];
            })
            ->toArray();
    }

    public function getItems($charId, $sessionKey)
    {
        return (object)[
            'status' => 1,
            'items' => $this->shopItems()
        ];
    }

    public function buyItem($charId, $sessionKey, $shopId)
    {
        try {
            return DB::transaction(function () use ($charId, $shopId) {
                $itemConfig = FriendshipShopItem::find($shopId);

                if (!$itemConfig) {
                    return (object)['status' => 0, 'result' => 'Item not found in shop.'];
                }

                $cost = $itemConfig->price;
                $rewardStr = $itemConfig->item;
                $kunaiId = 'material_1002'; // Friendship Kunai

                $char = Character::lockForUpdate()->find($charId);
                
                $kunaiItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $kunaiId)
                    ->first();

                if (!$kunaiItem || $kunaiItem->quantity < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough Friendship Kunai!'];
                }

                if ($kunaiItem->quantity == $cost) {
                    $kunaiItem->delete();
                    $newKunaiQty = 0;
                } else {
                    $kunaiItem->quantity -= $cost;
                    $kunaiItem->save();
                    $newKunaiQty = $kunaiItem->quantity;
                }

                $this->applyReward($char, $rewardStr);

                return (object)[
                    'status' => 1,
                    'reward' => $rewardStr,
                    'kunai' => $newKunaiQty
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function applyReward($char, $rewardStr)
    {
        if (str_contains($rewardStr, "gold_")) {
            $amt = (int) str_replace("gold_", "", $rewardStr);
            $char->gold += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "xp_")) {
            $amt = (int) str_replace("xp_", "", $rewardStr);
            $char->xp += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "tokens_")) {
            $amt = (int) str_replace("tokens_", "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } elseif (str_contains($rewardStr, "token_")) {
            $amt = (int) str_replace("tokens_", "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } else {
            \App\Helpers\ItemHelper::addItem($char->id, $rewardStr, 1);
        }
    }
}
