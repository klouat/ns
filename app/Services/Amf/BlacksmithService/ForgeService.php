<?php

namespace App\Services\Amf\BlacksmithService;

use App\Models\BlacksmithItem;
use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForgeService
{
    public function forgeItem($charId, $sessionKey, $targetItemId, $currency)
    {
        try {
            return DB::transaction(function () use ($charId, $targetItemId, $currency) {
                // 1. Get Recipe
                $recipe = BlacksmithItem::where('item_id', $targetItemId)->first();
                
                if (!$recipe) {
                    return (object)['status' => 0, 'error' => 'Recipe not found!'];
                }

                $materials = $recipe->materials;
                $quantities = $recipe->quantities;
                $reqWeapon = $recipe->req_weapon;

                $cost = ($currency === 'tokens') ? $recipe->token_price : $recipe->gold_price;
                
                $char = Character::lockForUpdate()->find($charId);
                $user = User::lockForUpdate()->find($char->user_id);

                // 2. Check Currency
                if ($currency === 'tokens') {
                    if ($user->tokens < $cost) {
                        return (object)['status' => 2, 'result' => 'Not enough Tokens!'];
                    }
                } else {
                    if ($char->gold < $cost) {
                        return (object)['status' => 2, 'result' => 'Not enough Gold!'];
                    }
                }

                // 3. Check Materials (Only if paying with Gold)
                if ($currency !== 'tokens') {
                    foreach ($materials as $index => $matId) {
                        $qtyNeeded = $quantities[$index];
                        
                        $invItem = CharacterItem::where('character_id', $charId)
                            ->where('item_id', $matId)
                            ->first();

                        if (!$invItem || $invItem->quantity < $qtyNeeded) {
                            return (object)['status' => 2, 'result' => 'Not enough materials!'];
                        }
                    }
                }

                // 4. Check Required Weapon (if applicable)
                if ($reqWeapon) {
                    $weaponItem = CharacterItem::where('character_id', $charId)
                        ->where('item_id', $reqWeapon)
                        ->first();
                    
                    if (!$weaponItem) {
                        return (object)['status' => 2, 'result' => 'Required weapon not found!'];
                    }
                }

                // 5. Deduct Currency
                if ($currency === 'tokens') {
                    $user->tokens -= $cost;
                    $user->save();
                } else {
                    $char->gold -= $cost;
                    $char->save();
                }

                // 6. Deduct Materials (Only if paying with Gold)
                if ($currency !== 'tokens') {
                    foreach ($materials as $index => $matId) {
                        $qtyNeeded = $quantities[$index];
                        $invItem = CharacterItem::where('character_id', $charId)
                            ->where('item_id', $matId)
                            ->first();
                            
                        if ($invItem->quantity == $qtyNeeded) {
                            $invItem->delete();
                        } else {
                            $invItem->quantity -= $qtyNeeded;
                            $invItem->save();
                        }
                    }
                }

                // 7. Deduct Required Weapon (consume it)
                if ($reqWeapon) {
                    $weaponItem = CharacterItem::where('character_id', $charId)
                        ->where('item_id', $reqWeapon)
                        ->first();
                    
                    if ($weaponItem->quantity > 1) {
                         $weaponItem->quantity -= 1;
                         $weaponItem->save();
                    } else {
                        $weaponItem->delete();
                    }
                }

                // 8. Add Target Item
                $targetInv = CharacterItem::where('character_id', $charId)
                    ->where('item_id', $targetItemId)
                    ->first();

                if ($targetInv) {
                    $targetInv->quantity += 1;
                    $targetInv->save();
                } else {
                    CharacterItem::create([
                        'character_id' => $charId,
                        'item_id' => $targetItemId,
                        'quantity' => 1,
                        'category' => 'weapon'
                    ]);
                }

                // 9. Response
                $reqIds = [];
                $reqQtys = [];

                if ($currency !== 'tokens') {
                    $reqIds = $materials;
                    $reqQtys = $quantities;
                }

                if ($reqWeapon) {
                    $reqIds[] = $reqWeapon;
                    $reqQtys[] = 1;
                }

                return (object)[
                    'status' => 1,
                    'item' => $targetItemId,
                    'requirements' => [$reqIds, $reqQtys],
                    'result' => "Item forged successfully!"
                ];
            });

        } catch (\Exception $e) {
            Log::error("BlacksmithService.ForgeService.forgeItem error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
