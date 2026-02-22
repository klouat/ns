<?php

namespace App\Services\Amf;

use App\Services\Amf\WelcomeLoginService\WelcomeLogicService;

class WelcomeLoginService
{
    private WelcomeLogicService $logicService;

    public function __construct()
    {
        $this->logicService = new WelcomeLogicService();
    }

    public function get($charId, $sessionKey)
    {
        return $this->logicService->get($charId, $sessionKey);
    }

    public function claim($charId, $sessionKey, $dayIdx)
    {
        return $this->logicService->claim($charId, $sessionKey, $dayIdx);
    }
}
