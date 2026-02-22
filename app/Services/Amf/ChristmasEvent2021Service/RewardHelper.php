<?php

namespace App\Services\Amf\ChristmasEvent2021Service;

use App\Models\CharacterItem;
use App\Models\User;

class RewardHelper
{
    /**
     * Add reward to character inventory
     * Handles different reward types: material:qty, gold_amount, tokens_amount, items, skills
     */
    public static function addRewardToCharacter($char, $rewardId)
    {
        // Parse reward format: "material_930:10" or "gold_50000" or "tokens_100" or "skill_123"
        
        // Check if it has quantity (format: id:qty)
        if (strpos($rewardId, ':') !== false) {
            list($itemId, $quantity) = explode(':', $rewardId);
            $quantity = (int)$quantity;
        } else {
            $itemId = $rewardId;
            $quantity = 1;
        }

        // Handle gold rewards
        if (strpos($itemId, 'gold_') === 0) {
            $amount = (int)str_replace('gold_', '', $itemId);
            $char->gold += $amount;
            $char->save();
            return;
        }

        // Handle token rewards
        if (strpos($itemId, 'tokens_') === 0) {
            $amount = (int)str_replace('tokens_', '', $itemId);
            $user = User::find($char->user_id);
            $user->tokens += $amount;
            $user->save();
            return;
        }

        // Handle material/item rewards
        $item = CharacterItem::firstOrCreate(
            ['character_id' => $char->id, 'item_id' => $itemId],
            ['quantity' => 0, 'category' => 'item']
        );
        
        $item->quantity += $quantity;
        $item->save();
    }
}
