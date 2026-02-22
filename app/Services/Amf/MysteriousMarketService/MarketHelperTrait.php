<?php

namespace App\Services\Amf\MysteriousMarketService;

use App\Models\CharacterSkill;
use App\Models\LimitedStoreItem;

trait MarketHelperTrait
{
    private function hasSkill($charId, $skillId) {
        return CharacterSkill::where('character_id', $charId)->where('skill_id', $skillId)->exists();
    }

    private function generateRandomItems() {
        $allItems = LimitedStoreItem::where('is_active', true)->get();
        $groupedItems = $allItems->groupBy(function ($item) {
            return $item->group_id ?? ('single_' . $item->id);
        });

        if ($groupedItems->isEmpty()) {
            return [];
        }

        $randomGroup = $groupedItems->random();
        $sortedGroup = $randomGroup->sortBy('sort_order');

        return $sortedGroup->pluck('item_id')->toArray();
    }
}
