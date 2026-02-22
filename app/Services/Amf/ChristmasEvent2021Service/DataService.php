<?php

namespace App\Services\Amf\ChristmasEvent2021Service;

use App\Models\Character;
use App\Models\CharacterItem;
use App\Models\MoyaiGachaReward;

class DataService
{
    private const MATERIAL_GACHA = 'material_874';

    public function getData($sessionKey, $charId, $accountId)
    {
        $char = Character::find($charId);
        
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found'];
        }

        $coinItem = CharacterItem::where('character_id', $charId)
            ->where('item_id', self::MATERIAL_GACHA)
            ->first();
        
        $coins = $coinItem ? $coinItem->quantity : 0;

        return (object)[
            'status' => 1,
            'coin' => $coins
        ];
    }

    public function getRewardList($sessionKey, $charId)
    {
        $rewardList = $this->getGachaRewardList();

        return (object)[
            'status' => 1,
            'top'    => $rewardList['top'],
            'mid'    => $rewardList['mid'],
            'common' => $rewardList['common']
        ];
    }

    private function getGachaRewardList()
    {
        $topRewards = MoyaiGachaReward::where('tier', 'top')->pluck('reward_id')->toArray();
        $midRewards = MoyaiGachaReward::where('tier', 'mid')->pluck('reward_id')->toArray();
        $commonRewards = MoyaiGachaReward::where('tier', 'common')->pluck('reward_id')->toArray();

        return [
            'top' => $topRewards,
            'mid' => $midRewards,
            'common' => $commonRewards
        ];
    }
}
