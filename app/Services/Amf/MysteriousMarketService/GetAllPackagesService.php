<?php

namespace App\Services\Amf\MysteriousMarketService;

use App\Models\LimitedStoreItem;
use App\Models\CharacterSkill;
use Illuminate\Support\Facades\Log;

class GetAllPackagesService
{
    public function getAllPackagesList($charId, $sessionKey)
    {
        try {
            $items = LimitedStoreItem::where('is_active', true)->get();
            
            $filteredItems = $items->groupBy(function($item) {
                return $item->group_id ?? ('single_' . $item->id);
            })->map(function($group) {
                return $group->sortByDesc('sort_order')->first();
            });

            $ownedSkills = CharacterSkill::where('character_id', $charId)->pluck('skill_id')->toArray();

            $packages = [];
            foreach ($filteredItems as $item) {
                $packages[] = (object)[
                    'advanced_skill' => $item->item_id,
                    'owned' => in_array($item->item_id, $ownedSkills)
                ];
            }

            return (object)[
                'status' => 1,
                'packages' => $packages
            ];
        } catch (\Exception $e) {
            Log::error("MysteriousMarket.getAllPackagesList error: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
