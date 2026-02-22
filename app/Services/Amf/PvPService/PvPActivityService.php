<?php

namespace App\Services\Amf\PvPService;

use App\Models\Character;
use Illuminate\Support\Facades\DB;

class PvPActivityService
{
    public function getBattleActivity($char_id, $session_key)
    {
        $battles = DB::table('pvp_battles')
            ->where('host_id', $char_id)
            ->orWhere('enemy_id', $char_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $activityList = [];
        foreach($battles as $b) {
            $isHost = $b->host_id == $char_id;
            $opponentId = $isHost ? $b->enemy_id : $b->host_id;
            $opponent = Character::find($opponentId);
            
            $activityList[] = (object)[
                'battle_id' => $b->id,
                'opponent_name' => $opponent ? $opponent->name : 'Unknown',
                'result' => $b->winner_id == $char_id ? 'WIN' : ($b->winner_id ? 'LOSE' : 'DRAW'),
                'time' => $b->created_at
            ];
        }

        return (object)[
            'status'  => 1,
            'battles' => $activityList
        ];
    }
}
