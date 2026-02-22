<?php

namespace App\Services\Amf;

use App\Services\Amf\JusticeBadgeEvent2024Service\BadgeService;

class JusticeBadgeEvent2024Service
{
    private BadgeService $badgeService;

    public function __construct()
    {
        $this->badgeService = new BadgeService();
    }

    public function getEventData($charId, $sessionKey)
    {
        return $this->badgeService->getEventData($charId, $sessionKey);
    }

    public function exchange($charId, $sessionKey, $requirement)
    {
        return $this->badgeService->exchange($charId, $sessionKey, $requirement);
    }
}
