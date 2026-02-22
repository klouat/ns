<?php

namespace App\Services\Amf\SystemLoginService;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterService
{
    public function registerUser($username, $email, $password, $serverString)
    {
        $checkUser = User::where('username', $username)->first();
        if ($checkUser) {
            return (object)[
                'status' => 2,
                'result' => 'Username already exists!'
            ];
        }

        $checkEmail = User::where('email', $email)->first();
        if ($checkEmail) {
            return (object)[
                'status' => 2,
                'result' => 'Email already exists!'
            ];
        }

        try {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->name = $username;
            $user->save();

            return (object)[
                'status' => 1,
                'result' => 'Registered Successfully!'
            ];
        } catch (\Exception $e) {
            return (object)[
                'status' => 0,
                'error' => 'Internal Server Error'
            ];
        }
    }
}
