<?php

namespace App\Services\Amf;

use App\Services\Amf\EudemonGardenService\DataService;
use App\Services\Amf\EudemonGardenService\BuyService;
use App\Services\Amf\EudemonGardenService\BattleService;

class EudemonGardenService
{
    private DataService $dataService;
    private BuyService $buyService;
    private BattleService $battleService;

    public function __construct()
    {
        $this->dataService = new DataService();
        $this->buyService = new BuyService();
        $this->battleService = new BattleService();
    }

    public function getData($sessionKey, $charId)
    {
        return $this->dataService->getData($sessionKey, $charId);
    }

    public function buyTries($sessionKey, $charId)
    {
        return $this->buyService->buyTries($sessionKey, $charId);
    }

    public function startHunting($charId, $bossNum, $sessionKey)
    {
        return $this->battleService->startHunting($charId, $bossNum, $sessionKey);
    }

    public function finishHunting($charId, $bossNum, $code, $hash, $sessionKey, $battleData)
    {
        return $this->battleService->finishHunting($charId, $bossNum, $code, $hash, $sessionKey, $battleData);
    }
}
