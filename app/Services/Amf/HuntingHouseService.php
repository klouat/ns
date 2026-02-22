<?php

namespace App\Services\Amf;

use App\Models\Character;
use App\Models\CharacterHuntingHouse;
use App\Models\CharacterItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\CharacterPet;
use App\Helpers\ExperienceHelper;

class HuntingHouseService
{
    private $badgeId = "material_509";
    
    // Boss data based on reference
    private $bossData = [
        "ene_81" => [
            "id" => ["ene_81"],
            "name" => "Ginkotsu",
            "msn_bg" => "mission_21",
            "lvl" => 10,
            "rank" => 4,
            "desc" => "These wolf-like Ginkotsu live in the forest between the Wind Village and the Fire Village. \nThey are the best hunters in the forest. They always attack adventurers and passersby in the forest.",
            "rewards" => ["wpn_121", "material_01", "material_02"],
            "gold" => 4000,
            "xp" => 1491
        ],
        "ene_82" => [
            "id" => ["ene_82"],
            "name" => "Shikigami Yanki",
            "msn_bg" => "mission_22",
            "lvl" => 20,
            "rank" => 4,
            "desc" => "Summoned by Kojima. Kojima ordered Yanki to protect Kojima's Third Laboratory.\nYanki will not allow anyone to enter the laboratory.",
            "rewards" => ["wpn_123", "material_01", "material_02", "material_03"],
            "gold" => 5000,
            "xp" => 4078
        ],
        "ene_83" => [
            "id" => ["ene_83"],
            "name" => "Gedo Sessho Seki",
            "msn_bg" => "mission_83",
            "lvl" => 25,
            "rank" => 3,
            "desc" => "Summoned by Kojima, Gedo Sessho Seki is the guardian of his Third Laboratory.",
            "rewards" => ["wpn_152", "material_01", "material_02", "material_03"],
            "gold" => 6000,
            "xp" => 6745
        ],
        "ene_84" => [
            "id" => ["ene_84", "ene_85"],
            "name" => "Tengu",
            "msn_bg" => "mission_84",
            "lvl" => 30,
            "rank" => 3,
            "desc" => "Kojima used Kinjutsu to turn dead bodies into these zombie Tengu.\nThese Tengu can recall and use all jutsu they learned when they were alive. Only the summoner may control them.",
            "rewards" => ["wpn_120", "wpn_122", "material_01", "material_02", "material_03", "material_04"],
            "gold" => 7000,
            "xp" => 10602
        ],
        "ene_86" => [
            "id" => ["ene_86"],
            "name" => "Byakko",
            "msn_bg" => "mission_86",
            "lvl" => 40,
            "rank" => 2,
            "desc" => "Many years ago, an escaped ninja attempted to use a Kinjutsu to merge his body with the summon monster 'Byakko' to gain immortality. \nHe failed. 'Byakko' devoured him - it is a violent and cruel monster.",
            "rewards" => ["wpn_124", "material_01", "material_02", "material_03", "material_04", "material_05"],
            "gold" => 14000,
            "xp" => 17834
        ],
        "ene_106" => [
            "id" => ["ene_106"],
            "name" => "Ape King",
            "msn_bg" => "mission_106",
            "lvl" => 50,
            "rank" => 2,
            "desc" => "Living in the Ape Mountain, Ape King is the leader of all apes.",
            "rewards" => ["wpn_276", "material_01", "material_02", "material_03", "material_04", "material_05"],
            "gold" => 20000,
            "xp" => 48750
        ],
        "ene_120" => [
            "id" => ["ene_120"],
            "name" => "Battle Turtle",
            "msn_bg" => "mission_120",
            "lvl" => 55,
            "rank" => 2,
            "desc" => "Originated from the north, the Battle Turtle is known of its age, which is signified by the thorns on its shell.",
            "rewards" => ["wpn_330", "material_01", "material_02", "material_03", "material_04", "material_05"],
            "gold" => 25000,
            "xp" => 58152
        ],
        "ene_155" => [
            "id" => ["ene_155"],
            "name" => "Soul General Mutoh",
            "msn_bg" => "mission_155",
            "lvl" => 60,
            "rank" => 1,
            "desc" => "Once dead, but resurrected with (Kinjutsu: Reverse Soul Resurrection), Soul General is now a rank SS-criminal who escaped to the Samu Village and serve as a secret weapon.",
            "rewards" => ["wpn_786", "material_03", "material_04", "material_05"],
            "gold" => 30000,
            "xp" => 68225
        ]
    ];

    public function getData($charId, $sessionKey)
    {
        $today = Carbon::today()->toDateString();
        $record = CharacterHuntingHouse::firstOrCreate(['character_id' => $charId]);
        $dailyClaimed = ($record->last_daily_claim_date === $today);

        $badgeCount = CharacterItem::where('character_id', $charId)
            ->where('item_id', $this->badgeId)
            ->value('quantity') ?? 0;

        // Structured mapping for 5 zones
        $zones = [
            ['easyBoss' => ["ene_81"], 'hardBoss' => null],                 // Zone 1: Ginkotsu (Easy)
            ['easyBoss' => null,        'hardBoss' => ["ene_82", "ene_83"]], // Zone 2: Yanki & Gedo (Hard)
            ['easyBoss' => ["ene_84"], 'hardBoss' => null],                 // Zone 3: Tengu (Easy)
            ['easyBoss' => null,        'hardBoss' => ["ene_86", "ene_106"]], // Zone 4: Byakko & Ape King (Hard)
            ['easyBoss' => null,        'hardBoss' => ["ene_120", "ene_155"]] // Zone 5: Battle Turtle & Soul General (Hard)
        ];

        $bosses = [];
        foreach ($this->bossData as $id => $data) {
            $bosses[$id] = (object)[
                'name' => $data['name'],
                'description' => $data['desc'],
                'rewards' => $data['rewards'],
                'lvl' => $data['lvl'],
                'gold' => $data['gold'],
                'xp' => $data['xp']
            ];
            if (count($data['id']) > 1) {
                foreach($data['id'] as $subId) {
                    if ($subId != $id) $bosses[$subId] = $bosses[$id];
                }
            }
        }

        return (object)[
            'status' => 1,
            'zones' => array_map(function($zone) { return (object)$zone; }, $zones),
            'bosses' => (object)$bosses,
            'material' => $badgeCount,
            'daily_claim' => $dailyClaimed
        ];
    }

    public function startHunting($charId, $zoneId, $sessionKey)
    {
        return DB::transaction(function () use ($charId, $zoneId) {
            $cost = ($zoneId >= 3) ? 10 : 5; 
            
            $char = Character::lockForUpdate()->find($charId);
            $badge = CharacterItem::where('character_id', $charId)->where('item_id', $this->badgeId)->lockForUpdate()->first();

            if (!$badge || $badge->quantity < $cost) {
                return (object)['status' => 2, 'result' => 'You need ' . $cost . ' Kari Badges for this zone!'];
            }

            $badge->quantity -= $cost;
            $badge->save();

            $battleCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 32); 
            $hashInput = (string)$zoneId . (string)$charId . $battleCode; 
            $hash = hash('sha256', $hashInput);

            return (object)[
                'status' => 1,
                'code' => $battleCode,
                'hash' => $hash
            ];
        });
    }

    public function dailyClaim($charId, $sessionKey)
    {
        return DB::transaction(function () use ($charId) {
            $today = Carbon::today()->toDateString();
            $char = Character::lockForUpdate()->find($charId);
            $record = CharacterHuntingHouse::where('character_id', $charId)->lockForUpdate()->first();
            
            if ($record && $record->last_daily_claim_date === $today) {
                return (object)['status' => 2, 'result' => 'Already claimed today!'];
            }

            $user = User::find($char->user_id);
            $amount = ($user && $user->account_type == 1) ? 10 : 5;

            $badge = CharacterItem::firstOrCreate(
                ['character_id' => $charId, 'item_id' => $this->badgeId],
                ['quantity' => 0, 'category' => 'material']
            );
            $badge->quantity += $amount;
            $badge->save();

            $record->last_daily_claim_date = $today;
            $record->save();

            return (object)['status' => 1, 'material' => $badge->quantity];
        });
    }

    public function buyMaterial($charId, $sessionKey, $amount)
    {
        $totalCost = 5 * $amount;
        return DB::transaction(function () use ($charId, $amount, $totalCost) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);

            if (!$user || $user->tokens < $totalCost) {
                return (object)['status' => 2, 'result' => 'Not enough Tokens!'];
            }

            $user->tokens -= $totalCost;
            $user->save();

            $badge = CharacterItem::where('character_id', $charId)->where('item_id', $this->badgeId)->lockForUpdate()->first();
            if ($badge) {
                $badge->quantity += $amount;
                $badge->save();
            } else {
                CharacterItem::create(['character_id' => $charId, 'item_id' => $this->badgeId, 'quantity' => $amount, 'category' => 'material']);
            }

            return (object)['status' => 1, 'material' => CharacterItem::where('character_id', $charId)->where('item_id', $this->badgeId)->value('quantity')];
        });
    }

    // Zone number to boss keys mapping (mirrors the zones array in getData)
    private $zoneBossMap = [
        1 => ['ene_81'],                // Zone 1: Ginkotsu (Easy)
        2 => ['ene_82', 'ene_83'],      // Zone 2: Yanki & Gedo (Hard)
        3 => ['ene_84'],                // Zone 3: Tengu (Easy)
        4 => ['ene_86', 'ene_106'],     // Zone 4: Byakko & Ape King (Hard)
        5 => ['ene_120', 'ene_155'],    // Zone 5: Battle Turtle & Soul General (Hard)
    ];

    public function finishHunting($charId, $bossNum, $code, $hash, $sessionKey, $battleData)
    {
        return DB::transaction(function () use ($charId, $bossNum) {
             $char = Character::lockForUpdate()->find($charId);
             
             // $bossNum is actually the zone number (1-5), map it to boss keys
             $bossKeys = $this->zoneBossMap[$bossNum] ?? [];

             // Aggregate rewards from all bosses in this zone
             $goldReward    = 0;
             $xpReward      = 0;
             $possibleRewards = [];

             foreach ($bossKeys as $key) {
                 $data = $this->bossData[$key] ?? null;
                 if ($data) {
                     $goldReward += $data['gold'];
                     $xpReward   += $data['xp'];
                     foreach ($data['rewards'] as $reward) {
                         if (!in_array($reward, $possibleRewards)) {
                             $possibleRewards[] = $reward;
                         }
                     }
                 }
             }

             // Fallback if zone mapping fails
             if (empty($bossKeys)) {
                 $goldReward    = 5000;
                 $xpReward      = 2000;
             }

             $earnedItems = [];

             foreach ($possibleRewards as $itemId) {
                 // Determine Drop Rate
                 $chance = 0;
                 if (str_starts_with($itemId, 'wpn_') || str_starts_with($itemId, 'set_') || str_starts_with($itemId, 'back_') || str_starts_with($itemId, 'accessory_') || str_starts_with($itemId, 'hair_')) {
                     $chance = 10; // 10% chance for equipment/rares
                 } elseif (str_starts_with($itemId, 'material_')) {
                     $chance = 100; // 100% chance for materials
                 } else {
                     $chance = 30; // 30% default chance
                 }

                 // Roll
                 if (mt_rand(1, 100) <= $chance) {
                     $earnedItems[] = $itemId;

                     // Add to Inventory using Helper
                     \App\Helpers\ItemHelper::addItem($charId, $itemId, 1);
                 }
             }

             $char->gold += $goldReward;
             
             // Use ExperienceHelper to add XP and check level up
             $levelUp = ExperienceHelper::addCharacterXp($char, $xpReward);
             $char->save();

             if ($char->equipped_pet_id) {
                $pet = CharacterPet::where('character_id', $charId)->where('id', $char->equipped_pet_id)->lockForUpdate()->first();
                if ($pet) {
                     $petXpReward = floor($xpReward * 0.2);
                     ExperienceHelper::addPetXp($pet, $petXpReward, $char->level);
                     $pet->save();
                }
             }

             return (object)[
                'status' => 1,
                'xp' => $char->xp,
                'level' => $char->level,
                'level_up' => $levelUp,
                'result' => [$goldReward, $xpReward, $earnedItems]
            ];
        });
    }



    public function getItems($charId, $sessionKey)
    {
        try {
            $items = \App\Models\HuntingHouseItem::orderBy('sort_order')->get();
            
            \Illuminate\Support\Facades\Log::info("HuntingHouse.getItems", [
                'count'      => $items->count(),
                'first_item' => $items->first() ? $items->first()->toArray() : null,
            ]);

            $formattedItems = [];
            foreach ($items as $item) {
                $formattedItems[] = (object)[
                    'item' => $item->item_id,
                    'requirements' => (object)[
                        'materials' => array_values($item->materials),
                        'qty' => array_values($item->quantities)
                    ]
                ];
            }

            return (object)[
                'status' => 1,
                'items' => $formattedItems
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("HuntingHouse.getItems error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function forgeItem($charId, $sessionKey, $targetItemId)
    {
        try {
            return DB::transaction(function () use ($charId, $targetItemId) {
                // 1. Get Recipe
                $recipe = \App\Models\HuntingHouseItem::where('item_id', $targetItemId)->first();
                
                if (!$recipe) {
                    return (object)['status' => 0, 'error' => 'Recipe not found!'];
                }

                $materials = $recipe->materials;
                $quantities = $recipe->quantities;

                // 2. Check Requirements
                foreach ($materials as $index => $matId) {
                    $qtyNeeded = $quantities[$index];
                    
                    $invItem = CharacterItem::where('character_id', $charId)
                        ->where('item_id', $matId)
                        ->first();

                    if (!$invItem || $invItem->quantity < $qtyNeeded) {
                        return (object)['status' => 2, 'result' => 'Not enough materials!'];
                    }
                }

                // 3. Deduct Materials
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

                // 4. Add Target Item
                \App\Helpers\ItemHelper::addItem($charId, $targetItemId, 1);

                // 5. Response
                return (object)[
                    'status' => 1,
                    'item' => $targetItemId,
                    'requirements' => [$materials, $quantities]
                ];
            });

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("HuntingHouse.forgeItem error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
