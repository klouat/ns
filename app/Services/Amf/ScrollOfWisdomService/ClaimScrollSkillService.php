<?php

namespace App\Services\Amf\ScrollOfWisdomService;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\CharacterSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClaimScrollSkillService
{
    use ScrollHelperTrait;

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
}
