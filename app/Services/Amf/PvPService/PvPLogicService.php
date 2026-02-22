<?php

namespace App\Services\Amf\PvPService;

use App\Models\Character;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PvPLogicService
{
    public function checkAccess($char_id, $session_key)
    {
        $char = Character::find($char_id);
        if (!$char) return (object)['status' => 0, 'result' => 'Character not found'];
        
        if (!$this->validateSession($char->user_id, $session_key)) {
             return (object)['status' => 0, 'result' => 'Session expired!'];
        }

        return (object)[
            'status' => 1,
            'url'    => 'pvp.swf'
        ];
    }

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
    
    private function validateSession($userId, $sessionKey)
    {
        $user = \App\Models\User::find($userId);
        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }

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

    public function reportBug($char_id, $session_key, $title, $description)
    {
        Log::info('PvP Bug Report', [
            'char_id' => $char_id,
            'title' => $title,
            'desc' => $description
        ]);
        
        return (object)[
            'status' => 1,
            'result' => 'Report received.'
        ];
    }
}
