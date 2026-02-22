<?php

namespace App\Services\Amf\BattleSystemService;

class MiniGameService
{
    public function startSageScrollMiniGame($charId, $sessionKey)
    {
        return (object)[
            'status' => 1
        ];
    }

    public function finishSageScrollMiniGame($charId, $score, $sessionKey)
    {
        return (object)[
            'status' => 1
        ];
    }
}
