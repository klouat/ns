<?php

namespace App\Services\Amf\GiveawayService;

use App\Models\Character;
use App\Models\Giveaway;
use App\Models\CharacterGiveaway;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ParticipateService
{
    public function participate($charId, $sessionKey, $giveawayId)
    {
        return DB::transaction(function () use ($charId, $giveawayId) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'result' => 'Character not found'];

            $giveaway = Giveaway::lockForUpdate()->find($giveawayId);
            if (!$giveaway) return (object)['status' => 0, 'result' => 'Giveaway not found'];

            if ($giveaway->end_at < Carbon::now()) {
                return (object)['status' => 0, 'result' => 'Giveaway has ended'];
            }

            $exists = CharacterGiveaway::where('character_id', $charId)
                ->where('giveaway_id', $giveawayId)
                ->exists();
            if ($exists) {
                return (object)['status' => 0, 'result' => 'Already joined'];
            }

            foreach ($giveaway->requirements as $req) {
                if ($req['type'] === 'level') {
                    if ($char->level < $req['total']) {
                        return (object)['status' => 0, 'result' => 'Level requirement not met'];
                    }
                } elseif ($req['type'] === 'gold_fee') {
                    if ($char->gold < $req['total']) {
                        return (object)['status' => 0, 'result' => 'Not enough gold'];
                    }
                    $char->gold -= $req['total'];
                    $char->save();
                }
            }

            CharacterGiveaway::create([
                'character_id' => $charId,
                'giveaway_id' => $giveawayId,
                'joined_at' => Carbon::now()
            ]);

            return (object)['status' => 1];
        });
    }
}
