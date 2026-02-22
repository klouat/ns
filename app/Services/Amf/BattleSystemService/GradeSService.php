<?php

namespace App\Services\Amf\BattleSystemService;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class GradeSService
{
    public function getMissionSData($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $userEnergy = \App\Models\UserEnergy::firstOrCreate(
            ['user_id' => $char->user_id],
            ['energy_grade_s' => 100, 'max_energy_grade_s' => 100]
        );

        $mat899 = \App\Models\CharacterItem::where('character_id', $charId)
                        ->where('item_id', 'material_899')
                        ->value('quantity') ?? 0;
        $mat900 = \App\Models\CharacterItem::where('character_id', $charId)
                        ->where('item_id', 'material_900')
                        ->value('quantity') ?? 0;
        
        $spins = \App\Models\CharacterMissionSpin::where('character_id', $charId)->value('spins_available') ?? 0;

        $rewards = [
            (object)['id' => 1, 'type' => 'gold', 'quantity' => 5000, 'item_id' => 'gold_5000'],
            (object)['id' => 2, 'type' => 'xp', 'quantity' => 2000, 'item_id' => 'xp_2000'],
            (object)['id' => 3, 'type' => 'tokens', 'quantity' => 10, 'item_id' => 'tokens_10'],
            (object)['id' => 4, 'type' => 'tp', 'quantity' => 20, 'item_id' => 'tp_20'],
            (object)['id' => 5, 'type' => 'item', 'quantity' => 1, 'item_id' => 'material_02'],
            (object)['id' => 6, 'type' => 'item', 'quantity' => 1, 'item_id' => 'essential_05']
        ];

        return (object)[
            'status' => 1,
            'stage' => 5,
            'energy' => $userEnergy->energy_grade_s,
            'max_energy' => $userEnergy->max_energy_grade_s,
            'spins' => $spins,
            'rewards' => $rewards,
            'materials' => (object)[
                'material_899' => $mat899,
                'material_900' => $mat900
            ]
        ];
    }

    public function buyRamenMissionS($charId, $sessionKey, $type, $amount)
    {
        return DB::transaction(function () use ($charId, $amount, $type) {
             $char = Character::lockForUpdate()->find($charId);
             $user = \App\Models\User::lockForUpdate()->find($char->user_id);
             
             $price = 0;
             if ($type == 'material_900') $price = 50; 
             else if ($type == 'material_899') $price = 15;
             
             $totalCost = $price * $amount;
             
             if ($user->tokens < $totalCost) {
                 return (object)['status' => 2, 'result' => 'Not enough tokens'];
             }
             
             $user->tokens -= $totalCost;
             $user->save();
             
             $item = \App\Models\CharacterItem::firstOrCreate(
                 ['character_id' => $charId, 'item_id' => $type],
                 ['quantity' => 0, 'category' => 'material']
             );
             $item->quantity += $amount;
             $item->save();
             
             $mat899 = \App\Models\CharacterItem::where('character_id', $charId)->where('item_id', 'material_899')->value('quantity') ?? 0;
             $mat900 = \App\Models\CharacterItem::where('character_id', $charId)->where('item_id', 'material_900')->value('quantity') ?? 0;
             
             return (object)[
                 'status' => 1,
                 'materials' => (object)[
                    'material_899' => $mat899,
                    'material_900' => $mat900
                 ]
             ];
        });
    }

    public function refillRamenMissionS($charId, $sessionKey, $type)
    {
        return DB::transaction(function () use ($charId, $type) {
             $char = Character::find($charId);
             $item = \App\Models\CharacterItem::where('character_id', $charId)->where('item_id', $type)->lockForUpdate()->first();
             
             if (!$item || $item->quantity < 1) {
                 return (object)['status' => 2, 'result' => 'Not enough ramen'];
             }
             
             $item->quantity -= 1;
             $item->save();
             
             $userEnergy = \App\Models\UserEnergy::where('user_id', $char->user_id)->first();
             if ($type == 'material_900') {
                 $userEnergy->energy_grade_s = $userEnergy->max_energy_grade_s;
             } else {
                 $userEnergy->energy_grade_s = min($userEnergy->max_energy_grade_s, $userEnergy->energy_grade_s + 50);
             }
             $userEnergy->save();
             
             $mat899 = \App\Models\CharacterItem::where('character_id', $charId)->where('item_id', 'material_899')->value('quantity') ?? 0;
             $mat900 = \App\Models\CharacterItem::where('character_id', $charId)->where('item_id', 'material_900')->value('quantity') ?? 0;

             return (object)[
                 'status' => 1,
                 'result' => 'Energy Refilled',
                 'energy' => $userEnergy->energy_grade_s,
                 'materials' => (object)[
                    'material_899' => $mat899,
                    'material_900' => $mat900
                 ]
             ];
        });
    }
}
