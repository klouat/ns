<?php

namespace App\Services\Amf;

use App\Services\Amf\DailyScratchService\ScratchService;

class DailyScratchService
{
    private ScratchService $scratchService;

    public function __construct()
    {
        $this->scratchService = new ScratchService();
    }

    public function getData($charId, $sessionKey)
    {
        return $this->scratchService->getData($charId, $sessionKey);
    }

    public function scratch($charId, $sessionKey)
    {
        return $this->scratchService->scratch($charId, $sessionKey);
    }
}
