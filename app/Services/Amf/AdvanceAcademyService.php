<?php

namespace App\Services\Amf;

use App\Services\Amf\AdvanceAcademyService\SkillService;

class AdvanceAcademyService
{
    private SkillService $skillService;

    public function __construct()
    {
        $this->skillService = new SkillService();
    }

    public function upgradeSkill($charId, $sessionKey, $skillId)
    {
        return $this->skillService->upgradeSkill($charId, $sessionKey, $skillId);
    }

    public function getChains()
    {
        return $this->skillService->getChains();
    }
}
