<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterDailyScratch;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyScratchService
{
    public function getData($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $today = now()->toDateString();
                $scratch = CharacterDailyScratch::firstOrCreate(
                    ['character_id' => $charId],
                    [
                        'tickets' => 1,
                        'consecutive_days' => 1,
                        'last_scratch_date' => null
                    ]
                );

                $user = User::find($char->user_id);
                $isEmblem = $user && ($user->account_type == 1 || $user->emblem_duration > 0 || $user->emblem_duration == -1);

                $lastDate = $scratch->last_scratch_date ? $scratch->last_scratch_date->toDateString() : null;

                // Reset tickets daily
                if ($lastDate !== $today) {
                    $yesterday = now()->subDay()->toDateString();
                    
                    if ($lastDate === $yesterday) {
                        $scratch->consecutive_days = min($scratch->consecutive_days + 1, 7);
                    } else {
                        $scratch->consecutive_days = 1;
                    }
                    
                    // Tickets = consecutive days + 2 bonus for emblem
                    $scratch->tickets = $scratch->consecutive_days + ($isEmblem ? 2 : 0);
                    $scratch->last_scratch_date = $today;
                    $scratch->save();
                }

                return [
                    'status' => 1,
                    'ticket' => $scratch->tickets,
                    'consecutive' => $scratch->consecutive_days
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyScratch.getData: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function scratch($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return ['status' => 0, 'error' => 'Character not found'];

                $scratch = CharacterDailyScratch::where('character_id', $charId)->lockForUpdate()->first();
                if (!$scratch || $scratch->tickets <= 0) {
                    return ['status' => 2, 'result' => 'No more tickets today!'];
                }

                $scratch->tickets -= 1;
                $scratch->last_scratch_date = now()->toDateString();
                $scratch->save();

                // Get rewards from gamedata
                $gamedata = json_decode(file_get_contents(storage_path('app/gamedata.json')), true);
                $scratchConfig = null;
                foreach ($gamedata as $item) {
                    if ($item['id'] === 'scratch') {
                        $scratchConfig = $item['data'];
                        break;
                    }
                }

                if (!$scratchConfig) {
                    return ['status' => 0, 'error' => 'Scratch config not found'];
                }

                $rewards = $scratchConfig['rewards'];
                
                // Roll for grand prize (rare)
                $roll = rand(1, 100);
                if ($roll === 100) {
                    $reward = $scratchConfig['grand_prize'][0];
                } else {
                    $reward = $rewards[array_rand($rewards)];
                }

                // Process reward (tokens_~15, gold_~50000, xp_~2%, item_id, etc)
                // We'll return the string and let the client assume it's added if addRewards is called?
                // Actually, the server should add it.
                $this->applyReward($char, $reward);

                return [
                    'status' => 1,
                    'reward' => $reward
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyScratch.scratch: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function applyReward($char, $rewardStr)
    {
        // Replace %s if needed
        $rewardStr = str_replace('%s', $char->gender, $rewardStr);

        if (str_contains($rewardStr, "tokens_")) {
            $amt = (int) str_replace(["tokens_", "~"], "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } elseif (str_contains($rewardStr, "gold_")) {
            $amt = (int) str_replace(["gold_", "~"], "", $rewardStr);
            $char->gold += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "xp_")) {
            // xp_~2% (not implemented simple math here, but could be)
            // For now just ignore percentages or give fixed small amount
        } elseif (str_contains($rewardStr, "tp_")) {
            $amt = (int) str_replace(["tp_", "~"], "", $rewardStr);
            $char->tp += $amt;
            $char->save();
        } else {
            // Assume it's an item/skill/etc.
            // Check if it's a pet
            if (str_starts_with($rewardStr, "pet_")) {
                // Not standard logic for items, but maybe add to character_items?
                // Normally pets go to character_pets.
            } else {
                // Add to character_items
                $category = 'item';
                if (str_starts_with($rewardStr, 'wpn_')) $category = 'weapon';
                elseif (str_starts_with($rewardStr, 'back_')) $category = 'back';
                elseif (str_starts_with($rewardStr, 'set_')) $category = 'set';
                elseif (str_starts_with($rewardStr, 'hair_')) $category = 'hair';
                elseif (str_starts_with($rewardStr, 'material_')) {
                    $category = 'material';
                }
                elseif (str_starts_with($rewardStr, 'essential_')) {
                    $category = 'essential';
                }
                elseif (str_starts_with($rewardStr, 'accessory_')) $category = 'accessory';

                $item = CharacterItem::where('character_id', $char->id)
                    ->where('item_id', $rewardStr)
                    ->first();
                if ($item) {
                    $item->quantity += 1;
                    $item->save();
                } else {
                    CharacterItem::create([
                        'character_id' => $char->id,
                        'item_id' => $rewardStr,
                        'quantity' => 1,
                        'category' => $category
                    ]);
                }
            }
        }
    }
}
