<?php

namespace App\Services\Amf\DailyRewardService;

use App\Models\User;
use App\Helpers\ItemHelper;

class RewardHelper
{
    public static function applyRewardString($char, $rewardStr)
    {
        if (str_contains($rewardStr, "gold_")) {
            $amt = (int) str_replace(["gold_", "~"], "", $rewardStr);
            $char->gold += $amt;
            $char->save();
        } elseif (str_contains($rewardStr, "tokens_")) {
            $amt = (int) str_replace(["tokens_", "~"], "", $rewardStr);
            $user = User::find($char->user_id);
            if ($user) {
                $user->tokens += $amt;
                $user->save();
            }
        } elseif (str_contains($rewardStr, "tp_")) {
            $amt = (int) str_replace(["tp_", "~"], "", $rewardStr);
            $char->tp += $amt;
            $char->save();
        } else {
            self::addItem($char->id, $rewardStr);
        }
    }

    public static function addItem($charId, $itemId)
    {
        ItemHelper::addItem($charId, $itemId, 1);
    }
}
