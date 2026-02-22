<?php

namespace App\Services\Amf\SystemLoginService;

use App\Models\User;
use App\Models\Character;

class AccountService
{
    public function getAllCharacters($uid, $sessionkey)
    {
        if (!$this->validateSession($uid, $sessionkey)) {
            return (object)['status' => 0, 'error' => 'Session expired!'];
        }

        $characters = Character::where('user_id', $uid)->get();
        $user = User::find($uid);
        
        $accountData = [];
        
        foreach ($characters as $char) {
            $rank = match($char->rank) {
                'Chunin'                => 2,
                'Tensai Chunin'         => 3,
                'Jounin'                => 4,
                'Tensai Jounin'         => 5,
                'Special Jounin'        => 6,
                'Tensai Special Jounin' => 7,
                'Ninja Tutor'           => 8,
                'Senior Ninja Tutor'    => 9,
                default                 => 1
            };

            $accountData[] = (object)[
                'char_id' => $char->id,
                'acc_id' => $uid,
                'character_name' => $char->name,
                'character_level' => $char->level,
                'character_xp' => $char->xp,
                'character_gender' => $char->gender,
                'character_rank' => $rank,
                'character_prestige' => $char->prestige,
                'character_element_1' => $char->element_1,
                'character_element_2' => $char->element_2,
                'character_element_3' => $char->element_3,
                'character_talent_1' => $char->talent_1,
                'character_talent_2' => $char->talent_2,
                'character_talent_3' => $char->talent_3,
                'character_gold' => $char->gold,
                'character_tp' => $char->tp,
            ];
        }

        return (object)[
            'status' => 1,
            'error' => 0,
            'account_type' => $user->account_type ?? 0,
            'emblem_duration' => $user->emblem_duration ?? -1,
            'tokens' => $user->tokens ?? 0,
            'total_characters' => count($accountData),
            'account_data' => $accountData
        ];
    }

    private function validateSession($userId, $sessionKey)
    {
        $user = User::find($userId);

        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }
}
