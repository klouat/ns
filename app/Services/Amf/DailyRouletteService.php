<?php

namespace App\Services\Amf;

use App\Services\Amf\DailyRouletteService\RouletteService;

class DailyRouletteService
{
    private RouletteService $rouletteService;

    public function __construct()
    {
        $this->rouletteService = new RouletteService();
    }

    public function getData($charId, $sessionKey)
    {
        return $this->rouletteService->getData($charId, $sessionKey);
    }

    public function spin($charId, $sessionKey)
    {
        return $this->rouletteService->spin($charId, $sessionKey);
    }
}
