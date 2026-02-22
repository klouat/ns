<?php

namespace App\Services\Amf;

use App\Services\Amf\GiveawayService\GetGiveawaysService;
use App\Services\Amf\GiveawayService\ParticipateService;
use App\Services\Amf\GiveawayService\GiveawayHistoryService;

class GiveawayService
{
    private GetGiveawaysService $getGiveawaysService;
    private ParticipateService $participateService;
    private GiveawayHistoryService $giveawayHistoryService;

    public function __construct()
    {
        $this->getGiveawaysService = new GetGiveawaysService();
        $this->participateService = new ParticipateService();
        $this->giveawayHistoryService = new GiveawayHistoryService();
    }

    public function get($charId, $sessionKey)
    {
        return $this->getGiveawaysService->get($charId, $sessionKey);
    }

    public function participate($charId, $sessionKey, $giveawayId)
    {
        return $this->participateService->participate($charId, $sessionKey, $giveawayId);
    }

    public function history($charId, $sessionKey)
    {
        return $this->giveawayHistoryService->history($charId, $sessionKey);
    }
}
