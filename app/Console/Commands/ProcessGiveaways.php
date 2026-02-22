<?php

namespace App\Console\Commands;

use App\Models\Giveaway;
use App\Models\GiveawayWinner;
use App\Models\CharacterGiveaway;
use App\Models\Character;
use App\Models\Mail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessGiveaways extends Command
{
    protected $signature = 'giveaway:process {--winners=5 : Number of winners to pick per giveaway}';

    protected $description = 'Process ended giveaways: pick winners and send reward mails';

    public function handle(): int
    {
        $giveaways = Giveaway::where('processed', false)
            ->where('end_at', '<=', Carbon::now())
            ->get();

        if ($giveaways->isEmpty()) {
            $this->info('No giveaways to process.');
            return 0;
        }

        $default_winner_count = (int) $this->option('winners');

        foreach ($giveaways as $giveaway) {
            $this->processGiveaway($giveaway, $default_winner_count);
        }

        $this->info('All giveaways processed.');
        return 0;
    }

    private function processGiveaway(Giveaway $giveaway, int $winner_count): void
    {
        try {
            DB::transaction(function () use ($giveaway, $winner_count) {
                $giveaway = Giveaway::lockForUpdate()->find($giveaway->id);

                if ($giveaway->processed) {
                    $this->warn("Giveaway #{$giveaway->id} already processed, skipping.");
                    return;
                }

                $participants = CharacterGiveaway::where('giveaway_id', $giveaway->id)
                    ->pluck('character_id')
                    ->toArray();

                if (empty($participants)) {
                    $this->warn("Giveaway #{$giveaway->id} '{$giveaway->title}' has no participants.");
                    $giveaway->processed = true;
                    $giveaway->save();
                    return;
                }

                // Pick random winners (capped to participant count)
                $pick_count       = min($winner_count, count($participants));
                $winner_char_ids  = collect($participants)->shuffle()->take($pick_count)->all();

                $winners = Character::whereIn('id', $winner_char_ids)->get()->keyBy('id');

                // Build rewards string for mail (comma-separated)
                $rewards_string = implode(',', $giveaway->prizes ?? []);

                $now = Carbon::now();

                foreach ($winner_char_ids as $char_id) {
                    $char = $winners[$char_id] ?? null;
                    if (!$char) continue;

                    // Record winner
                    GiveawayWinner::create([
                        'giveaway_id'    => $giveaway->id,
                        'character_id'   => $char_id,
                        'character_name' => $char->name,
                        'prize_won'      => $giveaway->prizes,
                        'won_at'         => $now,
                        'claimed'        => false,
                    ]);

                    // Send reward mail (type 5)
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

                    $this->line("  → Winner: [{$char_id}] {$char->name}");
                }

                $giveaway->processed = true;
                $giveaway->save();

                $this->info("Giveaway #{$giveaway->id} '{$giveaway->title}': {$pick_count} winner(s) selected, mails sent.");

                Log::info("Giveaway processed", [
                    'giveaway_id' => $giveaway->id,
                    'title'       => $giveaway->title,
                    'winners'     => $winner_char_ids,
                ]);
            });
        } catch (\Throwable $e) {
            $this->error("Failed to process giveaway #{$giveaway->id}: {$e->getMessage()}");
            Log::error("Giveaway processing failed", [
                'giveaway_id' => $giveaway->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }
}
