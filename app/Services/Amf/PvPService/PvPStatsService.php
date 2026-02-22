<?php

namespace App\Services\Amf\PvPService;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class PvPStatsService
{
    use PvPValidatorTrait;

    public function getCharacterStats($char_id, $session_key)
    {
        $char = Character::find($char_id);
        if ($char && !$this->validateSession($char->user_id, $session_key)) {
             return (object)['status' => 0, 'result' => 'Session expired!'];
        }

        $stats = DB::table('pvp_stats')->where('character_id', $char_id)->first();
        
        if (!$stats) {
            DB::table('pvp_stats')->insert([
                'character_id' => $char_id,
                'rank' => 0,
                'trophies' => 0,
                'points' => 0,
                'wins' => 0,
                'losses' => 0,
                'flee' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $stats = DB::table('pvp_stats')->where('character_id', $char_id)->first();
        }

        return (object)[
            'status' => 1,
            'data'   => (object)[
                'played'       => $stats->wins + $stats->losses + $stats->flee,
                'won'          => $stats->wins,
                'lost'         => $stats->losses,
                'pvp_points'   => $stats->points,
                'disconnected' => $stats->flee,
                'trophy'       => $stats->trophies,
                'pvp_version'  => '1.0',
                'pvp_news'     => 'Welcome to PvP!',
                'show_news'    => true
            ]
        ];
    }
}
