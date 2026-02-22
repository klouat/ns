<?php

namespace App\Services\Amf\SystemLoginService;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginService
{
    public function loginUser($username, $encryptedPassword, $char_, $bl, $bt, $char__, $item, $seed, $passLen)
    {
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return (object)[
                'status' => 2,
            ];
        }

        $decryptedPassword = $this->decryptPassword($encryptedPassword, $char__, $char_);
        
        if (!$decryptedPassword) {
            return (object)['status' => 2];
        }

        if (Hash::check($decryptedPassword, $user->password) == false) {
             return (object)['status' => 2];
        }

        $sessionKey = Str::random(32);
        $user->remember_token = $sessionKey;
        $user->save();

        return (object)[
            'status' => 1,
            'uid' => $user->id,
            'sessionkey' => $sessionKey,
            '__' => $char__,
            'events' => [
                'welcome_bonus',
                'mysterious-market',
                'chunin_package',
                'special-deals',
                'monster_hunter_2023',
                'dragon_hunt_2024',
                'justice-badge2024',
                'giveaway-center',
                'leaderboard',
                'tailedbeast',
                'dailygacha',
                'dragongacha',
                'exoticpackage',
                'thanksgiving2025',
                'elementalars',
                'xmass2025',
                'valentine2026',
                'phantom_kyunoki_2026',
            ],
            'clan_season' => 67,
            'crew_season' => 67,
            'sw_season' => 67,
            'banners' => []
        ];
    }

    public function decryptPassword($encryptedBase64, $keyString, $ivString)
    {
        try {
            $key = $keyString; 
            $iv = $this->pkcs5Pad($ivString, 16);
            $encryptedData = base64_decode($encryptedBase64);
            
            $decrypted = openssl_decrypt(
                $encryptedData, 
                'aes-128-cbc',
                $key, 
                OPENSSL_RAW_DATA, 
                $iv
            );

            return $decrypted;

        } catch (\Exception $e) {
            return false;
        }
    }

    private function pkcs5Pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
}
