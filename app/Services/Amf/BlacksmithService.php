<?php

namespace App\Services\Amf;

use App\Services\Amf\BlacksmithService\ForgeService;

class BlacksmithService
{
    private ForgeService $forgeService;

    public function __construct()
    {
        $this->forgeService = new ForgeService();
    }

    /**
     * forgeItem
     * Parameters: [charId, sessionKey, targetItemId, currency]
     */
    public function forgeItem($charId, $sessionKey, $targetItemId, $currency)
    {
        return $this->forgeService->forgeItem($charId, $sessionKey, $targetItemId, $currency);
    }
}
