<?php

namespace App\Services\Amf\GiveawayService;

use App\Models\Character;
use App\Models\User;
use App\Models\Giveaway;
use App\Models\CharacterGiveaway;
use App\Models\GiveawayWinner;
use App\Models\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GiveawayLogicService
{
    /**
     * Get active and recent giveaways
     * 
     * AMF Call: GiveawayService.get
     * Parameters: charId, sessionKey
     */
    public function get($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) {
            return (object)['status' => 0, 'result' => 'Character not found'];
        }

        // Auto-process any ended giveaways before returning data
        $this->processEndedGiveaways();

        // Get active giveaways and active-finished ones awaiting claim/view
        // We'll return active ones + recent 5 finished ones
        $activeGiveaways = Giveaway::where('end_at', '>', Carbon::now())
            ->orderBy('end_at', 'asc')
            ->get();
            
        // We might also want to show recently finished ones where winners are picked
        $recentFinished = Giveaway::where('end_at', '<=', Carbon::now())
            ->where('processed', true)
            ->orderBy('end_at', 'desc')
            ->take(3)
            ->get();

        $allGiveaways = $activeGiveaways->concat($recentFinished);
        
        $formattedGiveaways = [];

        foreach ($allGiveaways as $giveaway) {
            // Check if joined
            $joined = CharacterGiveaway::where('character_id', $charId)
                ->where('giveaway_id', $giveaway->id)
                ->exists();

            // Calculate participants count
            $participantsCount = CharacterGiveaway::where('giveaway_id', $giveaway->id)->count();

            // Format requirements with progress
            $formattedReqs = [];
            foreach ($giveaway->requirements as $req) {
                $progress = 0;
                $completed = false;

                if ($req['type'] === 'level') {
                    $progress = $char->level;
                    if ($progress >= $req['total']) $progress = $req['total'];
                } elseif ($req['type'] === 'gold_fee') {
                    // For fee, progress is usually 0 until they pay (join), 
                    // but AS shows it as requirement. 
                    // If joined, it's met. If not, it shows if they HAVE enough gold?
                    // Usually "progress" means what they Have.
                    $progress = $char->gold >= $req['total'] ? $req['total'] : $char->gold;
                }
                
                // If already joined, all reqs are met visually
                if ($joined) {
                    $progress = $req['total'];
                }

                $formattedReqs[] = (object)[
                    'name' => $req['name'],
                    'progress' => $progress,
                    'total' => $req['total']
                ];
            }

            // Get winners if processed
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

            // Time remaining (seconds)
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

    /**
     * Participate in a giveaway
     * 
     * AMF Call: GiveawayService.participate
     * Parameters: charId, sessionKey, giveawayId
     */
    public function participate($charId, $sessionKey, $giveawayId)
    {
        return DB::transaction(function () use ($charId, $giveawayId) {
            $char = Character::lockForUpdate()->find($charId);
            if (!$char) return (object)['status' => 0, 'result' => 'Character not found'];

            $giveaway = Giveaway::lockForUpdate()->find($giveawayId);
            if (!$giveaway) return (object)['status' => 0, 'result' => 'Giveaway not found'];

            // Check if active
            if ($giveaway->end_at < Carbon::now()) {
                return (object)['status' => 0, 'result' => 'Giveaway has ended'];
            }

            // Check if already joined
            $exists = CharacterGiveaway::where('character_id', $charId)
                ->where('giveaway_id', $giveawayId)
                ->exists();
            if ($exists) {
                return (object)['status' => 0, 'result' => 'Already joined'];
            }

            // Check requirements and deduct fees
            foreach ($giveaway->requirements as $req) {
                if ($req['type'] === 'level') {
                    if ($char->level < $req['total']) {
                        return (object)['status' => 0, 'result' => 'Level requirement not met'];
                    }
                } elseif ($req['type'] === 'gold_fee') {
                    if ($char->gold < $req['total']) {
                        return (object)['status' => 0, 'result' => 'Not enough gold'];
                    }
                    // Deduct gold
                    $char->gold -= $req['total'];
                    $char->save();
                }
                // Add logic for tokens if needed
            }

            // Join
            CharacterGiveaway::create([
                'character_id' => $charId,
                'giveaway_id' => $giveawayId,
                'joined_at' => Carbon::now()
            ]);

            return (object)['status' => 1];
        });
    }

    /**
     * Get inactive/history giveaways
     * 
     * AMF Call: GiveawayService.history
     * Parameters: charId, sessionKey
     */
    public function history($charId, $sessionKey)
    {
        // Get past processed giveaways
        $history = Giveaway::where('processed', true)
            ->where('end_at', '<=', Carbon::now())
            ->orderBy('end_at', 'desc')
            ->limit(20)
            ->get();

        $formattedHistory = [];
        foreach ($history as $h) {
            $formattedHistory[] = (object)[
                'id' => $h->id,
                'title' => $h->title,
                'description' => $h->description,
                'ended_at' => $h->end_at->format('Y-m-d'),
                'prizes' => array_values($h->prizes ?? [])
            ];
        }

        return (object)[
            'status' => 1,
            'giveaway' => $formattedHistory
        ];
    }

    /**
     * Auto-process ended giveaways: pick winners and send reward mails
     */
    private function processEndedGiveaways(): void
    {
        $ended = Giveaway::where('processed', false)
            ->where('end_at', '<=', Carbon::now())
            ->get();

        foreach ($ended as $giveaway) {
            try {
                DB::transaction(function () use ($giveaway) {
                    $giveaway = Giveaway::lockForUpdate()->find($giveaway->id);
                    if (!$giveaway || $giveaway->processed) return;

                    $participants = CharacterGiveaway::where('giveaway_id', $giveaway->id)
                        ->pluck('character_id')
                        ->toArray();

                    if (empty($participants)) {
                        $giveaway->processed = true;
                        $giveaway->save();
                        return;
                    }

                    $winner_count    = min(5, count($participants));
                    $winner_char_ids = collect($participants)->shuffle()->take($winner_count)->all();
                    $winners         = Character::whereIn('id', $winner_char_ids)->get()->keyBy('id');
                    $rewards_string  = implode(',', $giveaway->prizes ?? []);
                    $now             = Carbon::now();

                    foreach ($winner_char_ids as $char_id) {
                        $char = $winners[$char_id] ?? null;
                        if (!$char) continue;

                        GiveawayWinner::create([
                            'giveaway_id'    => $giveaway->id,
                            'character_id'   => $char_id,
                            'character_name' => $char->name,
                            'prize_won'      => $giveaway->prizes,
                            'won_at'         => $now,
                            'claimed'        => false,
                        ]);

                        Mail::create([
                            'character_id' => $char_id,
                            'sender_name'  => 'Giveaway',
                            'title'        => 'You Won: ' . $giveaway->title,
                            'body'         => "Congratulations! You are one of the winners of <b>{$giveaway->title}</b>!\n\nClaim your rewards below.",
                            'type'         => 5,
                            'rewards'      => $rewards_string,
                            'is_viewed'    => false,
                            'is_claimed'   => false,
                        ]);
                    }

                    $giveaway->processed = true;
                    $giveaway->save();

                    Log::info('Giveaway auto-processed', [
                        'giveaway_id' => $giveaway->id,
                        'winners'     => $winner_char_ids,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error('Failed to auto-process giveaway', [
                    'giveaway_id' => $giveaway->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }
}
