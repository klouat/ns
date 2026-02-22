<?php

namespace App\Services\Amf;

use App\Services\Amf\MaterialMarketService\MarketService;

class MaterialMarketService
{
    private MarketService $marketService;

    public function __construct()
    {
        $this->marketService = new MarketService();
    }

    public function getItems($charId, $sessionKey)
    {
        return $this->marketService->getItems($charId, $sessionKey);
    }

    public function forgeItem($charId, $sessionKey, $targetItemId, $type = null)
    {
        return $this->marketService->forgeItem($charId, $sessionKey, $targetItemId, $type);
    }
}
