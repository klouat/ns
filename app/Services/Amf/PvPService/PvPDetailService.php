<?php

namespace App\Services\Amf\PvPService;

use Illuminate\Support\Facades\DB;

class PvPDetailService
{
    public function getDetailBattle($char_id, $session_key, $battle_id)
    {
        $battle = DB::table('pvp_battles')->where('id', $battle_id)->first();

        if ($battle) {
             return (object)[
                'status' => 1,
                'result' => json_decode($battle->log) ?? 'No details available' 
            ];
        }

        return (object)[
            'status' => 0,
            'result' => 'Battle not found'
        ];
    }
}
