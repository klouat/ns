<?php

namespace App\Helpers;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterPet;
use App\Models\User;
use Illuminate\Support\Str;

class ItemHelper
{
    /**
     * Add an item (or currency/pet/xp) to a character.
     * Handles categorization automatically based on item ID prefix.
     */
    public static function addItem($charId, $itemId, $qty = 1)
    {
        // Handle Currency & Stats
        if (str_starts_with($itemId, 'gold_')) {
            $amount = (int) str_replace('gold_', '', $itemId);
            $char = Character::find($charId);
            if ($char) {
                $char->gold += $amount * $qty;
                $char->save();
            }
            return;
        }

        if (str_starts_with($itemId, 'tokens_')) {
            $amount = (int) str_replace('tokens_', '', $itemId);
            $char = Character::find($charId);
            if ($char) {
                $user = User::find($char->user_id);
                if ($user) {
                    $user->tokens += $amount * $qty;
                    $user->save();
                }
            }
            return;
        }

        if (str_starts_with($itemId, 'xp_')) {
            $amount = (int) str_replace('xp_', '', $itemId);
            $char = Character::find($charId);
            if ($char) {
                $char->xp += $amount * $qty;
                // Note: Level up check should ideally happen here or caller handles it
                // ExperienceHelper::checkCharacterLevelUp($char); 
                $char->save();
            }
            return;
        }

        if (str_starts_with($itemId, 'tp_')) {
            $amount = (int) str_replace('tp_', '', $itemId);
            $char = Character::find($charId);
            if ($char) {
                $char->tp += $amount * $qty;
                $char->save();
            }
            return;
        }

        // Handle Pets (Create unique pet instance)
        if (str_starts_with($itemId, 'pet_')) {
             for ($i = 0; $i < $qty; $i++) {
                CharacterPet::create([
                    'character_id' => $charId,
                    'pet_swf' => $itemId,
                    'pet_name' => ucwords(str_replace(['pet_', '_'], ['', ' '], $itemId)),
                    'pet_level' => 1,
                    'pet_xp' => 0,
                    'pet_mp' => 0,
                    'pet_skills' => '1,0,0,0,0,0'
                ]);
            }
            return;
        }

        // Determine Category for Inventory
        $category = 'item';
        if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
        elseif (str_starts_with($itemId, 'back_')) $category = 'back';
        elseif (str_starts_with($itemId, 'set_')) $category = 'set';
        elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
        elseif (str_starts_with($itemId, 'material_')) $category = 'material';
        elseif (str_starts_with($itemId, 'essential_')) $category = 'essential';
        elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';
        elseif (str_starts_with($itemId, 'skill_')) $category = 'skill';
        elseif (str_starts_with($itemId, 'item_')) $category = 'item';

        // Add/Update CharacterItem
        $item = CharacterItem::firstOrCreate(
            ['character_id' => $charId, 'item_id' => $itemId],
            ['quantity' => 0, 'category' => $category]
        );
        
        $item->quantity += $qty;
        $item->save();
    }
}
