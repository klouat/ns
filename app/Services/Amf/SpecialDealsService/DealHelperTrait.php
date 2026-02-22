<?php

namespace App\Services\Amf\SpecialDealsService;

trait DealHelperTrait
{
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
