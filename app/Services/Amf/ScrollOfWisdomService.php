<?php

namespace App\Services\Amf;

use App\Services\Amf\ScrollOfWisdomService\GetScrollDataService;
use App\Services\Amf\ScrollOfWisdomService\ClaimScrollSkillService;

class ScrollOfWisdomService
{
    private GetScrollDataService $getScrollDataService;
    private ClaimScrollSkillService $claimScrollSkillService;

    public function __construct()
    {
        $this->getScrollDataService = new GetScrollDataService();
        $this->claimScrollSkillService = new ClaimScrollSkillService();
    }

    public function getData($charId, $sessionKey)
    {
        return $this->getScrollDataService->getData($charId, $sessionKey);
    }

    public function claimSkill($charId, $sessionKey, $elementType)
    {
        return $this->claimScrollSkillService->claimSkill($charId, $sessionKey, $elementType);
    }
}
