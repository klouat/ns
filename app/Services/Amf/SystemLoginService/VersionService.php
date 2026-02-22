<?php

namespace App\Services\Amf\SystemLoginService;

use Illuminate\Support\Str;

class VersionService
{
    public function checkVersion($buildNum)
    {
        $ivSource = mt_rand(100000, 999999); 
        $key = Str::random(16);
        
        $data = [
            'status' => 1,
            '_' => $ivSource, 
            '__' => $key,
            'cdn' => '',
            '_rm' => '',
        ];

        return (object)$data;
    }
}
