<?php

namespace App\Services\Amf;

use App\Services\Amf\ValentineEvent2026Service\ValentineService;

class ValentineEvent2026Service
{
    private ValentineService $valentineService;

    public function __construct()
    {
        $this->valentineService = new ValentineService();
    }

    public function getPackage($charId, $sessionKey)
    {
        return $this->valentineService->getPackage($charId, $sessionKey);
    }

    public function buyItem($charId, $sessionKey, $type)
    {
        return $this->valentineService->buyItem($charId, $sessionKey, $type);
    }
}
