<?php

namespace App\Services\Amf\PvPService;

use Illuminate\Support\Facades\DB;

class PvPLeaderboardService
{
    public function getLeaderboard($char_id, $session_key)
    {
        $topStats = DB::table('pvp_stats')
            ->join('characters', 'pvp_stats.character_id', '=', 'characters.id')
            ->select('pvp_stats.*', 'characters.name', 'characters.hair_style', 'characters.hair_color', 'characters.skin_color')
            ->orderBy('pvp_stats.trophies', 'desc')
            ->limit(10)
            ->get();

        $data = [];
        $pos = 1;
        $myPos = 0;
        $myTrophy = 0;

        foreach ($topStats as $stat) {
            if ($stat->character_id == $char_id) {
                $myPos = $pos;
                $myTrophy = $stat->trophies;
            }
            
            $data[] = (object)[
                'id'      => $stat->character_id,
                'name'    => $stat->name,
                'trophy'  => $stat->trophies,
                'rank'    => $pos, 
                'char_id' => $stat->character_id,
                'sets'    => (object)[
                    'hair_style' => $stat->hair_style ?? 'hair_01_0',
                    'face'       => 'face_01', 
                    'hair_color' => $stat->hair_color ?? '0|0',
                    'skin_color' => $stat->skin_color ?? '0|0'
                ]
            ];
            $pos++;
        }
        
        if ($myPos == 0) {
            $myStat = DB::table('pvp_stats')->where('character_id', $char_id)->first();
            if ($myStat) {
                $myTrophy = $myStat->trophies;
                $myPos = DB::table('pvp_stats')->where('trophies', '>', $myStat->trophies)->count() + 1;
            }
        }

        return (object)[
            'status' => 1,
            'trophy' => $myTrophy,
            'pos'    => $myPos,
            'data'   => $data
        ];
    }
}
