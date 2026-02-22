<?php

namespace App\Services\Amf;

use App\Services\Amf\SenjutsuService\DiscoverSenjutsuService;
use App\Services\Amf\SenjutsuService\GetSenjutsuSkillsService;
use App\Services\Amf\SenjutsuService\UpgradeSenjutsuSkillService;
use App\Services\Amf\SenjutsuService\BuyPackageSSService;
use App\Services\Amf\SenjutsuService\EquipSenjutsuSkillService;

class SenjutsuService
{
    private DiscoverSenjutsuService $discoverSenjutsuService;
    private GetSenjutsuSkillsService $getSenjutsuSkillsService;
    private UpgradeSenjutsuSkillService $upgradeSenjutsuSkillService;
    private BuyPackageSSService $buyPackageSSService;
    private EquipSenjutsuSkillService $equipSenjutsuSkillService;

    public function __construct()
    {
        $this->discoverSenjutsuService = new DiscoverSenjutsuService();
        $this->getSenjutsuSkillsService = new GetSenjutsuSkillsService();
        $this->upgradeSenjutsuSkillService = new UpgradeSenjutsuSkillService();
        $this->buyPackageSSService = new BuyPackageSSService();
        $this->equipSenjutsuSkillService = new EquipSenjutsuSkillService();
    }

    public function discoverSenjutsu($charId, $sessionKey, $senjutsuType)
    {
        return $this->discoverSenjutsuService->discoverSenjutsu($charId, $sessionKey, $senjutsuType);
    }

    public function getSenjutsuSkills($charId, $sessionKey)
    {
        return $this->getSenjutsuSkillsService->getSenjutsuSkills($charId, $sessionKey);
    }

    public function upgradeSkill($charId, $sessionKey, $skillId, $isMax)
    {
        return $this->upgradeSenjutsuSkillService->upgradeSkill($charId, $sessionKey, $skillId, $isMax);
    }

    public function buyPackageSS($charId, $sessionKey, $packageIndex)
    {
        return $this->buyPackageSSService->buyPackageSS($charId, $sessionKey, $packageIndex);
    }

    public function equipSkill($charId, $sessionKey, $skills)
    {
        return $this->equipSenjutsuSkillService->equipSkill($charId, $sessionKey, $skills);
    }
}
