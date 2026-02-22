<?php

namespace App\Services\Amf;

use App\Services\Amf\DragonHuntEventService\BattleService;
use App\Services\Amf\DragonHuntEventService\MaterialService;
use App\Services\Amf\DragonHuntEventService\GachaService;

class DragonHuntEventService
{
    private BattleService $battleService;
    private MaterialService $materialService;
    private GachaService $gachaService;

    public function __construct()
    {
        $this->battleService = new BattleService();
        $this->materialService = new MaterialService();
        $this->gachaService = new GachaService();
    }

    public function startBattle($charId, $bossId, $mode, $agility, $enemyStats, $hash, $sessionKey)
    {
        return $this->battleService->startBattle($charId, $bossId, $mode, $agility, $enemyStats, $hash, $sessionKey);
    }

    public function buyMaterial($charId, $sessionKey, $materialId, $amount)
    {
        return $this->materialService->buyMaterial($charId, $sessionKey, $materialId, $amount);
    }

    public function finishBattle($charId, $bossId, $captured = 0)
    {
        return $this->battleService->finishBattle($charId, $bossId, $captured);
    }

    public function getGachaData($charId, $sessionKey, $accountId)
    {
        return $this->gachaService->getGachaData($charId, $sessionKey, $accountId);
    }

    public function getGachaRewards($charId, $sessionKey, $playType, $playQty)
    {
        return $this->gachaService->getGachaRewards($charId, $sessionKey, $playType, $playQty);
    }

    public function getGlobalGachaHistory($charId, $sessionKey)
    {
        return $this->gachaService->getGlobalGachaHistory($charId, $sessionKey);
    }
}
