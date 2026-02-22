<?php

namespace App\Services\Amf;

use App\Services\Amf\MonsterHunterEvent2023Service\HunterService;

class MonsterHunterEvent2023Service
{
    private HunterService $hunterService;

    public function __construct()
    {
        $this->hunterService = new HunterService();
    }

    public function getEventData($charId, $sessionKey)
    {
        return $this->hunterService->getEventData($charId, $sessionKey);
    }

    public function startBattle($charId, $bossId, $clientHash, $sessionKey)
    {
        return $this->hunterService->startBattle($charId, $bossId, $clientHash, $sessionKey);
    }

    public function finishBattle($charId, $bossId, $battleCode, $score, $hash, $battleData, $sessionKey)
    {
        return $this->hunterService->finishBattle($charId, $bossId, $battleCode, $score, $hash, $battleData, $sessionKey);
    }
}
