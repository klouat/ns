<?php

namespace App\Services\Amf\FriendService;

use App\Models\Character;
use Illuminate\Support\Facades\Log;

class RecruitService
{
    public function recruitable($charId, $sessionKey)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'result' => 'Character not found'];

        $char->is_recruitable = !$char->is_recruitable;
        $char->save();

        return (object)[
            'status' => 1,
            'recruitable' => (bool)$char->is_recruitable
        ];
    }

    public function recruitFriend($charId, $sessionKey, $friendId)
    {
        $isFriend = \App\Models\Friend::where('character_id', $charId)
            ->where('friend_id', $friendId)
            ->where('status', 1)
            ->exists();

        if (!$isFriend) {
            return (object)['status' => 2, 'result' => 'You can only recruit friends!'];
        }

        $friend = Character::find($friendId);
        if (!$friend || !$friend->is_recruitable) {
             return (object)['status' => 2, 'result' => 'This friend is not recruitable right now.'];
        }

        $char = Character::find($charId);
        $recruits = $char->recruits ?? [];
        
        if (!in_array($friendId, $recruits)) {
            if (count($recruits) >= 2) {
                $oldestRecruitId = array_shift($recruits);
                
                $oldRecruit = Character::find($oldestRecruitId);
                if ($oldRecruit) {
                    $oldRecruiters = $oldRecruit->recruiters ?? [];
                    $oldRecruiters = array_values(array_filter($oldRecruiters, fn($id) => $id != $charId));
                    $oldRecruit->recruiters = $oldRecruiters;
                    $oldRecruit->save();
                }
            }
            
            $recruits[] = $friendId;
            $char->recruits = $recruits;
            $char->save();
            
            $recruiters = $friend->recruiters ?? [];
            if (!in_array($charId, $recruiters)) {
                $recruiters[] = $charId;
                $friend->recruiters = $recruiters;
                $friend->save();
            }
        }


        $friendIds = array_map(function($id) {
            return 'char_' . $id;
        }, $recruits);
        
        $hash = !empty($friendIds) ? hash('sha256', (string)$friendIds[0]) : '';

        Log::info('recruitFriend response', [
            'recruits' => $recruits,
            'friendIds' => $friendIds,
            'hash' => $hash,
            'first_id' => $friendIds[0] ?? null
        ]);

        return (object)[
            'status' => 1,
            'recruiters' => [$friendIds, $hash]
        ];
    }
}
