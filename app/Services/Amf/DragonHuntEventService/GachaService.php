<?php

namespace App\Services\Amf\DragonHuntEventService;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GachaService
{
    private const MATERIAL_GACHA = 'material_773';
    
    private const REWARDS = [
        'top' => [
            "tokens_2000", "wpn_1121", "wpn_1122", "wpn_980", "wpn_986", "wpn_991", "wpn_992", 
            "wpn_1014", "wpn_1018", "wpn_1034", "wpn_1035", "wpn_1036", "wpn_1044", 
            "back_418", "back_422", "back_426", "back_435", "back_436", "back_458", 
            "back_466", "back_476", "back_477", "back_478", "back_480", 
            "pet_goldclowndragon", "pet_celebrationclowndragon", "pet_icebluedragon", 
            "pet_lightningdrake", "pet_undeadchaindragon", "pet_darkthundertripledragon", 
            "pet_dualcannontripledragon", "pet_minikirin", "pet_earthlavadragonturtle", 
            "material_819", "material_820", "material_821", "material_822", "material_823", "material_205"
        ],
        'mid' => [
            "hair_223_%s", "hair_225_%s", "hair_226_%s", "hair_229_%s", "hair_230_%s", 
            "hair_231_%s", "hair_233_%s", "hair_248_%s", "hair_250_%s", "hair_251_%s", 
            "hair_252_%s", "set_839_%s", "set_840_%s", "set_841_%s", "set_842_%s", 
            "set_843_%s", "set_844_%s", "set_845_%s", "set_846_%s", "set_847_%s", 
            "set_848_%s", "set_849_%s", "set_850_%s", 
            "material_200", "material_201", "material_202", "material_203", "material_204", 
            "material_1001", "essential_03", "essential_04", "essential_05", "item_52", "item_54", "tokens_50"
        ],
        'common' => [
            "material_773", "material_775", "material_776", "material_777", "material_778", 
            "material_779", "material_780", "material_781", "material_782", "material_783", 
            "material_784", "material_785", "material_786", "material_787", "material_788", 
            "material_789", "material_790", "material_791", "material_792", "material_793", 
            "material_794", "material_795", "material_796", "material_797", "material_798", 
            "material_799", "material_800", "material_801", "material_802", "material_803", 
            "material_804", "material_805", "material_806", "material_807", "material_808", 
            "material_809", "item_49", "item_50", "item_51", "item_33", "item_34", 
            "item_35", "item_36", "item_40", "item_39", "item_38", "item_37", "item_44", 
            "item_43", "item_42", "item_41", "item_24", "item_32", "item_31", "item_30", 
            "item_29", "item_28", "item_27", "item_26", "item_25", "item_23", "item_22", 
            "item_21", "item_20", "item_19", "item_18", "item_17", "item_16", "item_15", 
            "item_14", "item_13", "item_12", "item_11", "item_10", "item_09", "item_08", 
            "item_07", "item_06", "item_05", "item_04", "item_03", "item_02", "gold_10000"
        ]
    ];

    private const PRICE_TOKENS = [25, 50, 250];

    public function getGachaData($charId, $sessionKey, $accountId)
    {
        $char = Character::find($charId);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $ticket = CharacterItem::where('character_id', $charId)
            ->where('item_id', self::MATERIAL_GACHA)
            ->first();

        return (object)[
            'status' => 1,
            'coin' => $ticket ? $ticket->quantity : 0
        ];
    }

    public function getGachaRewards($charId, $sessionKey, $playType, $playQty)
    {
        return DB::transaction(function () use ($charId, $playType, $playQty) {
            $char = Character::lockForUpdate()->find($charId);
            $user = User::lockForUpdate()->find($char->user_id);

            if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

            $cost = 0;
            $currency = '';

            if ($playType === 'coins') {
                $currency = 'ticket';
                $cost = $playQty;
            } elseif ($playType === 'tokens') {
                $currency = 'token';
                if ($playQty == 1) $cost = self::PRICE_TOKENS[0];
                elseif ($playQty == 2) $cost = self::PRICE_TOKENS[1];
                elseif ($playQty == 6) $cost = self::PRICE_TOKENS[2];
                else return (object)['status' => 2, 'result' => 'Invalid quantity'];
            } else {
                return (object)['status' => 2, 'result' => 'Invalid play type'];
            }

            if ($currency === 'ticket') {
                $ticket = CharacterItem::where('character_id', $charId)
                    ->where('item_id', self::MATERIAL_GACHA)
                    ->first();
                
                if (!$ticket || $ticket->quantity < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tickets'];
                }

                $ticket->quantity -= $cost;
                $ticket->save();
            } else {
                if ($user->tokens < $cost) {
                    return (object)['status' => 2, 'result' => 'Not enough tokens'];
                }
                $user->tokens -= $cost;
                $user->save();
            }

            $rewards = [];
            
            for ($i = 0; $i < $playQty; $i++) {
                $rand = mt_rand(1, 100);
                if ($rand <= 5) {
                    $pool = self::REWARDS['top'];
                } elseif ($rand <= 30) {
                    $pool = self::REWARDS['mid'];
                } else {
                    $pool = self::REWARDS['common'];
                }

                $rawRewardId = $pool[array_rand($pool)];
                $rewardId = $this->resolveReward($rawRewardId, $char->gender);
                
                $rewards[] = "{$rewardId}:1";

                $this->addReward($char, $user, $rewardId, 1);

                \App\Models\DragonGachaHistory::create([
                    'character_id' => $charId,
                    'character_name' => $char->name,
                    'level' => $char->level,
                    'reward' => $rewardId,
                    'spin_count' => $playQty,
                    'obtained_at' => now(),
                ]);
            }

            $user->fresh();
            $currentTicket = CharacterItem::where('character_id', $charId)
                ->where('item_id', self::MATERIAL_GACHA)
                ->value('quantity') ?? 0;

            return (object)[
                'status' => 1,
                'rewards' => $rewards,
                'coin' => $currentTicket
            ];
        });
    }

    public function getGlobalGachaHistory($charId, $sessionKey)
    {
        try {
            $histories = \App\Models\DragonGachaHistory::orderBy('obtained_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($entry) {
                    return (object)[
                        'id' => $entry->character_id, 
                        'name' => $entry->character_name,
                        'level' => $entry->level,
                        'obtained_at' => $entry->obtained_at ? $entry->obtained_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                        'spin' => $entry->spin_count,
                        'reward' => $entry->reward
                    ];
                })
                ->values()
                ->all();

            return (object)[
                'status' => 1,
                'histories' => $histories
            ];
        } catch (\Exception $e) {
            \Log::error('DragonHuntEvent.getGlobalGachaHistory Error: ' . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Server Error'];
        }
    }

    private function resolveReward($rewardId, $gender)
    {
        if (strpos($rewardId, '%s') !== false) {
            return str_replace('%s', $gender, $rewardId);
        }
        return $rewardId;
    }

    private function addReward($char, $user, $rewardId, $qty)
    {
        \App\Helpers\ItemHelper::addItem($char->id, $rewardId, $qty);
    }
}
