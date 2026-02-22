<?php

namespace App\Services\Amf\PvPService;

use App\Models\User;

trait PvPValidatorTrait
{
    private function validateSession($userId, $sessionKey)
    {
        $user = User::find($userId);
        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }
}
