<?php

namespace App\Services\Amf;

use App\Services\Amf\TalentService\GetTalentService;
use App\Services\Amf\TalentService\UpgradeTalentService;
use App\Services\Amf\TalentService\BuyTPService;
use App\Services\Amf\TalentService\DiscoverTalentService;

class TalentService
{
    private GetTalentService $getTalentService;
    private UpgradeTalentService $upgradeTalentService;
    private BuyTPService $buyTPService;
    private DiscoverTalentService $discoverTalentService;

    public function __construct()
    {
        $this->getTalentService = new GetTalentService();
        $this->upgradeTalentService = new UpgradeTalentService();
        $this->buyTPService = new BuyTPService();
        $this->discoverTalentService = new DiscoverTalentService();
    }

    public function getTalentSkills($charId, $sessionKey)
    {
        return $this->getTalentService->getTalentSkills($charId, $sessionKey);
    }

    public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        return $this->upgradeTalentService->upgradeSkill($charId, $sessionKey, $skillId, $isMax);
    }

    public function buyPackageTP($charId, $sessionKey, $packageId)
    {
        return $this->buyTPService->buyPackageTP($charId, $sessionKey, $packageId);
    }

    public function discoverTalent($charId, $sessionKey, $type, $targetTalent)
    {
        return $this->discoverTalentService->discoverTalent($charId, $sessionKey, $type, $targetTalent);
    }
}