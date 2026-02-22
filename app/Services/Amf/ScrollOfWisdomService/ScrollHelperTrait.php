<?php

namespace App\Services\Amf\ScrollOfWisdomService;

use App\Models\User;

trait ScrollHelperTrait
{
    private function getSkillsForElement($type)
    {
        $rawSkills = \App\Helpers\ElementSkillHelper::getSkillsForElement($type);
        
        $advanceService = app(\App\Services\Amf\AdvanceAcademyService::class);
        if (!method_exists($advanceService, 'getChains')) {
            return $rawSkills; 
        }
        $chains = $advanceService->getChains();

        $skillChainMap = [];
        foreach ($chains as $element => $groups) {
            foreach ($groups as $groupName => $chainSkills) {
                foreach ($chainSkills as $idx => $sId) {
                    $skillChainMap[$sId] = ['group' => $groupName, 'index' => $idx];
                }
            }
        }

        $highestInGroup = [];
        $finalSkills = [];

        foreach ($rawSkills as $rSkill) {
            if (isset($skillChainMap[$rSkill])) {
                $group = $skillChainMap[$rSkill]['group'];
                $idx = $skillChainMap[$rSkill]['index'];
                
                if (!isset($highestInGroup[$group]) || $idx > $highestInGroup[$group]['index']) {
                    $highestInGroup[$group] = ['id' => $rSkill, 'index' => $idx];
                }
            } else {
                $finalSkills[] = $rSkill;
            }
        }

        foreach ($highestInGroup as $h) {
            $finalSkills[] = $h['id'];
        }

        return $finalSkills;
    }

    private function validateSession($userId, $sessionKey)
    {
        $user = User::find($userId);

        if (!$user || $user->remember_token !== $sessionKey) {
            return false;
        }
        return true;
    }
}
