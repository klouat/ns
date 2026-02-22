<?php

namespace App\Services\Amf\GiveawayService;

use App\Models\Character;
use App\Models\Giveaway;
use App\Models\CharacterGiveaway;
use App\Models\GiveawayWinner;
use Carbon\Carbon;

class GetGiveawaysService
{
    use GiveawayHelperTrait;

    public function get($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) {
            return (object)['status' => 0, 'result' => 'Character not found'];
        }

        $this->processEndedGiveaways();

        $activeGiveaways = Giveaway::where('end_at', '>', Carbon::now())
            ->orderBy('end_at', 'asc')
            ->get();
            
        $recentFinished = Giveaway::where('end_at', '<=', Carbon::now())
            ->where('processed', true)
            ->orderBy('end_at', 'desc')
            ->take(3)
            ->get();

        $allGiveaways = $activeGiveaways->concat($recentFinished);
        
        $formattedGiveaways = [];

        foreach ($allGiveaways as $giveaway) {
            $joined = CharacterGiveaway::where('character_id', $charId)
                ->where('giveaway_id', $giveaway->id)
                ->exists();

            $participantsCount = CharacterGiveaway::where('giveaway_id', $giveaway->id)->count();

            $formattedReqs = [];
            foreach ($giveaway->requirements as $req) {
                $progress = 0;

                if ($req['type'] === 'level') {
                    $progress = $char->level;
                    if ($progress >= $req['total']) $progress = $req['total'];
                } elseif ($req['type'] === 'gold_fee') {
                    $progress = $char->gold >= $req['total'] ? $req['total'] : $char->gold;
                }
                
                if ($joined) {
                    $progress = $req['total'];
                }

                $formattedReqs[] = (object)[
                    'name' => $req['name'],
                    'progress' => $progress,
                    'total' => $req['total']
                ];
            }

            $winners = [];
            if ($giveaway->processed) {
                $rawWinners = GiveawayWinner::where('giveaway_id', $giveaway->id)->take(50)->get();
                foreach ($rawWinners as $w) {
                    $winners[] = (object)[
                        'id' => $w->character_id,
                        'name' => $w->character_name
                    ];
                }
            }

            $now = Carbon::now();
            $secondsLeft = $giveaway->end_at->timestamp - $now->timestamp;
            if ($secondsLeft < 0) $secondsLeft = 0;

            $formattedGiveaways[] = (object)[
                'id' => $giveaway->id,
                'title' => $giveaway->title,
                'desc' => $giveaway->description,
                'prizes' => array_values($giveaway->prizes ?? []),
                'requirements' => $formattedReqs,
                'participants' => $participantsCount,
                'timestamp' => $secondsLeft,
                'joined' => $joined,
                'winners' => $winners
            ];
        }

        return (object)[
            'status' => 1,
            'giveaways' => $formattedGiveaways
        ];
    }
}
