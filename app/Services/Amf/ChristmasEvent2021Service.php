<?php

namespace App\Services\Amf;

use App\Services\Amf\ChristmasEvent2021Service\DataService;
use App\Services\Amf\ChristmasEvent2021Service\GachaService;
use App\Services\Amf\ChristmasEvent2021Service\BonusService;
use App\Services\Amf\ChristmasEvent2021Service\HistoryService;

class ChristmasEvent2021Service
{
    private DataService $dataService;
    private GachaService $gachaService;
    private BonusService $bonusService;
    private HistoryService $historyService;

    public function __construct()
    {
        $this->dataService = new DataService();
        $this->gachaService = new GachaService();
        $this->bonusService = new BonusService();
        $this->historyService = new HistoryService();
    }

    /**
     * Get event data (coins owned)
     */
    public function getData($sessionKey, $charId, $accountId)
    {
        return $this->dataService->getData($sessionKey, $charId, $accountId);
    }

    /**
     * Get gacha rewards (spin the gacha)
     */
    public function getGachaRewards($sessionKey, $charId, $playType, $playQty)
    {
        return $this->gachaService->getGachaRewards($sessionKey, $charId, $playType, $playQty);
    }

    /**
     * Get bonus rewards data
     */
    public function getBonusRewards($sessionKey, $charId, $accountId)
    {
        return $this->bonusService->getBonusRewards($sessionKey, $charId, $accountId);
    }

    /**
     * Claim bonus gacha rewards
     */
    public function claimBonusGachaRewards($sessionKey, $charId, $bonusIndex)
    {
        return $this->bonusService->claimBonusGachaRewards($sessionKey, $charId, $bonusIndex);
    }

    /**
     * Get reward list (prize list)
     */
    public function getRewardList($sessionKey, $charId)
    {
        return $this->dataService->getRewardList($sessionKey, $charId);
    }

    /**
     * Get personal gacha history
     */
    public function getPersonalGachaHistory($charId, $sessionKey)
    {
        return $this->historyService->getPersonalGachaHistory($charId, $sessionKey);
    }

    /**
     * Get global gacha history
     */
    public function getGlobalGachaHistory($charId, $sessionKey)
    {
        return $this->historyService->getGlobalGachaHistory($charId, $sessionKey);
    }
}
