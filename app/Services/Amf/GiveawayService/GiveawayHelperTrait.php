<?php

namespace App\Services\Amf\GiveawayService;

use App\Models\Giveaway;
use App\Models\CharacterGiveaway;
use App\Models\GiveawayWinner;
use App\Models\Character;
use App\Models\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

trait GiveawayHelperTrait
{
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
