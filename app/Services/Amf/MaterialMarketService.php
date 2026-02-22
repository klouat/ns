<?php

namespace App\Services\Amf;

use Illuminate\Support\Facades\Log;

class MaterialMarketService
{
    public function getItems($charId, $sessionKey)
    {
        try {
            $items = \App\Models\MaterialMarketItem::orderBy('sort_order')->get();
            
            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = (object)[
                    'item' => $item->item_id,
                    'requirements' => (object)[
                        'materials' => $item->materials,
                        'qty' => $item->quantities
                    ],
                    'end' => $item->expires_at ?? 'Unlimited'
                ];
            }

            return (object)[
                'status' => 1,
                'items' => $formattedItems
            ];
        } catch (\Exception $e) {
            Log::error("MaterialMarket.getItems error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function forgeItem($charId, $sessionKey, $targetItemId, $type = null)
    {
        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($charId, $targetItemId, $type) {
                // 1. Get Recipe
                $recipe = null;
                $prestigeCost = 0;

                if ($type === 'clan') {
                    $recipeData = $this->getClanRecipe($targetItemId);
                    if ($recipeData) {
                        $recipe = (object) [
                            'materials' => $recipeData['item_materials'],
                            'quantities' => $recipeData['item_mat_price'],
                        ];
                        $prestigeCost = $recipeData['item_prestige'];
                    }
                } else {
                    $dbRecipe = \App\Models\MaterialMarketItem::where('item_id', $targetItemId)->first();
                    if ($dbRecipe) {
                        $recipe = (object) [
                            'materials' => $dbRecipe->materials,
                            'quantities' => $dbRecipe->quantities,
                        ];
                    }
                }
                
                if (!$recipe) {
                    return (object)['status' => 0, 'error' => 'Recipe not found!'];
                }

                $materials = $recipe->materials;
                $quantities = $recipe->quantities;

                // 2. Check Requirements
                $character = \App\Models\Character::find($charId);
                if ($prestigeCost > 0 && $character->prestige < $prestigeCost) {
                    return (object)['status' => 2, 'result' => 'Not enough prestige!'];
                }

                foreach ($materials as $index => $matId) {
                    $qtyNeeded = $quantities[$index];
                    
                    $invItem = \App\Models\CharacterItem::where('character_id', $charId)
                        ->where('item_id', $matId)
                        ->first();

                    if (!$invItem || $invItem->quantity < $qtyNeeded) {
                        return (object)['status' => 2, 'result' => 'Not enough materials!'];
                    }
                }

                // 3. Deduct prestige and materials
                if ($prestigeCost > 0) {
                    $character->prestige -= $prestigeCost;
                    $character->save();
                }

                foreach ($materials as $index => $matId) {
                    $qtyNeeded = $quantities[$index];
                    $invItem = \App\Models\CharacterItem::where('character_id', $charId)
                        ->where('item_id', $matId)
                        ->first();
                        
                    if ($invItem->quantity == $qtyNeeded) {
                        $invItem->delete();
                    } else {
                        $invItem->quantity -= $qtyNeeded;
                        $invItem->save();
                    }
                }

                // 4. Add Target Item
                \App\Helpers\ItemHelper::addItem($charId, $targetItemId, 1);

                // 5. Response
                return (object)[
                    'status' => 1,
                    'item' => $targetItemId,
                    'requirements' => [$materials, $quantities], // Client might expect arrays here for strict indexing
                    'result' => 'Item forged successfully!'
                ];
            });

        } catch (\Exception $e) {
            Log::error("MaterialMarket.forgeItem error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function getClanRecipe($itemId)
    {
        $recipes = [
            "wpn_6004" => ["item_materials" => ["wpn_6001", "wpn_4001"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6006" => ["item_materials" => ["wpn_6002", "wpn_4002"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6008" => ["item_materials" => ["wpn_6003", "wpn_4003"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6010" => ["item_materials" => ["wpn_6005", "wpn_4004"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6012" => ["item_materials" => ["wpn_6007", "wpn_4005"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6014" => ["item_materials" => ["wpn_6009", "wpn_4006"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6016" => ["item_materials" => ["wpn_6011", "wpn_4007"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6018" => ["item_materials" => ["wpn_6013", "wpn_4008"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6029" => ["item_materials" => ["wpn_6015", "wpn_4009"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6030" => ["item_materials" => ["wpn_6017", "wpn_4010"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6031" => ["item_materials" => ["wpn_6019", "wpn_4011"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6035" => ["item_materials" => ["wpn_6020", "wpn_4012"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6036" => ["item_materials" => ["wpn_6021", "wpn_4013"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6037" => ["item_materials" => ["wpn_6022", "wpn_4014"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6038" => ["item_materials" => ["wpn_6023", "wpn_4015"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6039" => ["item_materials" => ["wpn_6024", "wpn_4016"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6040" => ["item_materials" => ["wpn_6025", "wpn_4017"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6041" => ["item_materials" => ["wpn_6026", "wpn_4018"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6042" => ["item_materials" => ["wpn_6027", "wpn_4019"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6043" => ["item_materials" => ["wpn_6028", "wpn_4020"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6044" => ["item_materials" => ["wpn_6032", "wpn_4021"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6045" => ["item_materials" => ["wpn_6033", "wpn_4022"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6046" => ["item_materials" => ["wpn_6034", "wpn_4023"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6056" => ["item_materials" => ["wpn_6047", "wpn_4024"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6057" => ["item_materials" => ["wpn_6048", "wpn_4025"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6058" => ["item_materials" => ["wpn_6049", "wpn_4026"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6065" => ["item_materials" => ["wpn_6050", "wpn_4027"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6066" => ["item_materials" => ["wpn_6051", "wpn_4028"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6067" => ["item_materials" => ["wpn_6052", "wpn_4029"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6068" => ["item_materials" => ["wpn_6053", "wpn_4030"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6069" => ["item_materials" => ["wpn_6054", "wpn_4031"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6077" => ["item_materials" => ["wpn_6055", "wpn_4032"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6078" => ["item_materials" => ["wpn_6059", "wpn_4033"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6079" => ["item_materials" => ["wpn_6060", "wpn_4034"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6080" => ["item_materials" => ["wpn_6061", "wpn_4035"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
            "wpn_6081" => ["item_materials" => ["wpn_6062", "wpn_4036"], "item_mat_price" => [1, 1], "item_prestige" => 50000],
        ];

        return $recipes[$itemId] ?? null;
    }
}

