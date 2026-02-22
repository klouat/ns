<?php

namespace App\Services\Amf;

use App\Services\Amf\SpecialDealsService\GetDealsService;
use App\Services\Amf\SpecialDealsService\BuyDealService;

class SpecialDealsService
{
    private GetDealsService $getDealsService;
    private BuyDealService $buyDealService;

    public function __construct()
    {
        $this->getDealsService = new GetDealsService();
        $this->buyDealService = new BuyDealService();
    }

    public function getDeals($charId, $sessionKey)
    {
        return $this->getDealsService->getDeals($charId, $sessionKey);
    }

    public function buy($charId, $sessionKey, $dealId)
    {
        return $this->buyDealService->buy($charId, $sessionKey, $dealId);
    }
}
