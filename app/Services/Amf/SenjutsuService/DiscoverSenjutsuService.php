<?php

namespace App\Services\Amf\SenjutsuService;

use App\Models\Character;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DiscoverSenjutsuService
{
    public function discoverSenjutsu($charId, $sessionKey, $senjutsuType)
    {
        try {
            return DB::transaction(function () use ($charId, $senjutsuType) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return (object)['status' => 0, 'error' => 'Character not found'];
                }

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) {
                    return (object)['status' => 0, 'error' => 'User not found'];
                }

                if ($char->senjutsu) {
                    return (object)['status' => 2, 'result' => 'You already learned a Sage Mode!'];
                }

                $senjutsuType = strtolower($senjutsuType);
                $costGold = 2000000;
                $costTokens = 0;

                if (!in_array($senjutsuType, ['toad', 'snake'])) {
                    return (object)['status' => 0, 'error' => 'Invalid Senjutsu type'];
                }

                if ($char->gold < $costGold) {
                    return (object)['status' => 2, 'result' => 'Not enough Gold!'];
                }

                $char->gold -= $costGold;
                $char->senjutsu = $senjutsuType;
                $char->save();

                $typeName = ucfirst($senjutsuType) . ' Sage Mode';

                return (object)[
                    'status' => 1,
                    'result' => "You have learned {$typeName}!",
                    'type' => $senjutsuType
                ];
            });
        } catch (\Exception $e) {
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
