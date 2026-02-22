<?php

namespace App\Services\Amf;

use App\Services\Amf\BattleSystemService\MissionService;
use App\Services\Amf\BattleSystemService\MiniGameService;
use App\Services\Amf\BattleSystemService\GradeSService;

class BattleSystemService
{
    private MissionService $missionService;
    private MiniGameService $miniGameService;
    private GradeSService $gradeSService;

    public function __construct()
    {
        $this->missionService = new MissionService();
        $this->miniGameService = new MiniGameService();
        $this->gradeSService = new GradeSService();
    }

    public function startMission($charId, $missionId, $enemyId, $enemyStats, $unknown, $hash, $sessionKey)
    {
        return $this->missionService->startMission($charId, $missionId, $enemyId, $enemyStats, $unknown, $hash, $sessionKey);
    }

    public function finishMission($charId, $missionId, $token, $hash, $score, $sessionKey, $battleData, $unknown)
    {
        return $this->missionService->finishMission($charId, $missionId, $token, $hash, $score, $sessionKey, $battleData, $unknown);
    }

    public function startSageScrollMiniGame($charId, $sessionKey)
    {
        return $this->miniGameService->startSageScrollMiniGame($charId, $sessionKey);
    }

    public function finishSageScrollMiniGame($charId, $score, $sessionKey)
    {
        return $this->miniGameService->finishSageScrollMiniGame($charId, $score, $sessionKey);
    }

    public function getMissionSData($charId, $sessionKey)
    {
        return $this->gradeSService->getMissionSData($charId, $sessionKey);
    }

    public function buyRamenMissionS($charId, $sessionKey, $type, $amount)
    {
        return $this->gradeSService->buyRamenMissionS($charId, $sessionKey, $type, $amount);
    }

    public function refillRamenMissionS($charId, $sessionKey, $type)
    {
        return $this->gradeSService->refillRamenMissionS($charId, $sessionKey, $type);
    }
}
