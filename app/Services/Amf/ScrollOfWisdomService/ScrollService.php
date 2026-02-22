<?php

namespace App\Services\Amf\ScrollOfWisdomService;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterSkill;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ScrollService
{
    public function getData($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            if (!$char) {
                return (object)['status' => 0, 'error' => 'Character not found'];
            }

            if (!$this->validateSession($char->user_id, $sessionKey)) {
                return (object)['status' => 0, 'error' => 'Session expired!'];
            }

            $scrollItem = CharacterItem::where('character_id', $charId)
                ->where('item_id', 'essential_10')
                ->first();

            $count = $scrollItem ? $scrollItem->quantity : 0;

            return (object)[
                'status' => 1,
                'scrolls' => $count
            ];
        } catch (\Exception $e) {
            Log::error("ScrollOfWisdomService.getData error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claimSkill($charId, $sessionKey, $elementType)
    {
        try {
            return DB::transaction(function () use ($charId, $sessionKey, $elementType) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) {
                    return (object)['status' => 0, 'error' => 'Character not found'];
                }

                if (!$this->validateSession($char->user_id, $sessionKey)) {
                    return (object)['status' => 0, 'error' => 'Session expired!'];
                }

                $scrollItem = CharacterItem::where('character_id', $charId)
                    ->where('item_id', 'essential_10')
                    ->first();

                if (!$scrollItem || $scrollItem->quantity < 1) {
                    return (object)['status' => 2, 'result' => 'You do not have any Secret Scroll of Wisdom!'];
                }

                $skillsToAdd = $this->getSkillsForElement($elementType);

                if (empty($skillsToAdd)) {
                    return (object)['status' => 0, 'error' => 'Invalid element selected or no skills found'];
                }

                $advanceService = app(\App\Services\Amf\AdvanceAcademyService::class);
                $chains = method_exists($advanceService, 'getChains') ? $advanceService->getChains() : [];
                $skillToGroupSkills = [];
                foreach ($chains as $elem => $groups) {
                    foreach ($groups as $grpName => $grpSkills) {
                        foreach ($grpSkills as $sId) {
                            $skillToGroupSkills[$sId] = $grpSkills;
                        }
                    }
                }

                foreach ($skillsToAdd as $skillId) {
                    if (isset($skillToGroupSkills[$skillId])) {
                        $groupSkills = $skillToGroupSkills[$skillId];

                        $existingSkills = CharacterSkill::where('character_id', $charId)
                            ->whereIn('skill_id', $groupSkills)
                            ->where('skill_id', '!=', $skillId)
                            ->get();

                        if ($existingSkills->isNotEmpty()) {
                            $idsToRemove = $existingSkills->pluck('skill_id')->toArray();
                            
                            CharacterSkill::whereIn('id', $existingSkills->pluck('id'))->delete();

                            $equipped = explode(',', $char->equipment_skills);
                            $changed = false;
                            foreach ($equipped as $k => $eqId) {
                                if (in_array($eqId, $idsToRemove)) {
                                    $equipped[$k] = $skillId;
                                    $changed = true;
                                }
                            }
                            if ($changed) {
                                $char->equipment_skills = implode(',', $equipped);
                                $char->save();
                            }
                        }
                    }

                    CharacterSkill::firstOrCreate([
                        'character_id' => $charId,
                        'skill_id' => $skillId
                    ]);
                }

                if ($scrollItem->quantity == 1) {
                    $scrollItem->delete();
                } else {
                    $scrollItem->decrement('quantity');
                }

                $allSkills = CharacterSkill::where('character_id', $charId)->pluck('skill_id')->toArray();
                $skillsString = implode(',', $allSkills);

                return (object)[
                    'status' => 1,
                    'skills' => $skillsString
                ];
            });
        } catch (\Exception $e) {
            Log::error("ScrollOfWisdomService.claimSkill error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

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
