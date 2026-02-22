<?php

namespace App\Services\Amf\PvPService;

use App\Models\Character;

class CheckAccessService
{
    use PvPValidatorTrait;

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
}
