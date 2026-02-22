<?php

namespace App\Services\Amf;

use App\Services\Amf\ConfrontingDeathEvent2025Service\DataService;
use App\Services\Amf\ConfrontingDeathEvent2025Service\BattleService;
use App\Services\Amf\ConfrontingDeathEvent2025Service\BonusService;
use App\Services\Amf\ConfrontingDeathEvent2025Service\SkillService;

class ConfrontingDeathEvent2025Service
{
    private DataService $dataService;
    private BattleService $battleService;
    private BonusService $bonusService;
    private SkillService $skillService;

    public function __construct()
    {
        $this->dataService = new DataService();
        $this->battleService = new BattleService();
        $this->bonusService = new BonusService();
        $this->skillService = new SkillService();
    }

    public function getBattleData($charId, $sessionKey)
    {
        return $this->dataService->getBattleData($charId, $sessionKey);
    }

    public function refillEnergy($charId, $sessionKey)
    {
        return $this->battleService->refillEnergy($charId, $sessionKey);
    }

    public function startBattle($charId, $bossId, $agility, $enemyStats, $hash, $sessionKey)
    {
        return $this->battleService->startBattle($charId, $bossId, $agility, $enemyStats, $hash, $sessionKey);
    }

    public function finishBattle($charId, $bossId, $code, $damage, $hash, $result, $sessionKey)
    {
        return $this->battleService->finishBattle($charId, $bossId, $code, $damage, $hash, $result, $sessionKey);
    }

    public function getBonusRewards($charId, $sessionKey)
    {
        return $this->dataService->getBonusRewards($charId, $sessionKey);
    }

    public function claimBonusRewards($charId, $sessionKey, $rewardIndex)
    {
        return $this->bonusService->claimBonusRewards($charId, $sessionKey, $rewardIndex);
    }

    public function buySkill($charId, $sessionKey, $skillIndex)
    {
        return $this->skillService->buySkill($charId, $sessionKey, $skillIndex);
    }
}
