<?php

namespace App\Services\Amf\TalentService;

use App\Models\Character;
use App\Models\User;
use App\Helpers\GameDataHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscoverTalentService
{
    public function discoverTalent($charId, $sessionKey, $type, $targetTalent)
    {
        try {
            return DB::transaction(function () use ($charId, $type, $targetTalent) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $user = User::lockForUpdate()->find($char->user_id);
                if (!$user) return (object)['status' => 0, 'error' => 'User not found'];

                $minLevel = ($type === 'Extreme') ? 40 : 50;
                if ($char->level < $minLevel) {
                    return (object)['status' => 2, 'result' => "You must be Level $minLevel to learn this!"];
                }

                $gameData = GameDataHelper::get_gamedata();
                
                $talentInfo = null;
                foreach ($gameData as $section) {
                    if ($section['id'] === 'talent_info') {
                        $talentInfo = $section['data'][$targetTalent] ?? null;
                        break;
                    }
                }

                if (!$talentInfo) {
                    return (object)['status' => 0, 'error' => 'Talent data not found!'];
                }

                if ($talentInfo['is_emblem'] && $user->account_type == 0) {
                    return (object)['status' => 2, 'result' => 'Upgrade to Emblem to learn this talent!'];
                }

                $priceGold = $talentInfo['price_gold'] ?? 0;
                $priceTokens = $talentInfo['price_token'] ?? 0;

                if ($char->gold < $priceGold || $user->tokens < $priceTokens) {
                    return (object)['status' => 2, 'result' => 'Not enough resources!'];
                }

                $newt = 1;
                if ($type === 'Extreme') {
                    if ($char->talent_1 != null) return (object)['status' => 2, 'result' => 'You already have an Extreme Talent!'];
                    $char->talent_1 = $targetTalent;
                    $newt = 1;
                } else if ($type === 'Secret') {
                    if ($char->talent_2 == null) {
                        $char->talent_2 = $targetTalent;
                        $newt = 2;
                    } else if ($char->talent_3 == null) {
                        $rankNum = match($char->rank) {
                            'Chunin' => 2,
                            'Tensai Chunin' => 3,
                            'Jounin' => 4,
                            'Tensai Jounin' => 5,
                            'Special Jounin' => 6,
                            'Tensai Special Jounin' => 7,
                            'Ninja Tutor' => 8,
                            'Senior Ninja Tutor' => 9,
                            default => 1
                        };
                        
                        if ($rankNum < 6) {
                            return (object)['status' => 2, 'result' => 'Must reach Special Jounin rank for the second Secret Talent slot!'];
                        }
                        
                        $char->talent_3 = $targetTalent;
                        $newt = 3;
                    } else {
                        return (object)['status' => 2, 'result' => 'No empty talent slots!'];
                    }
                }

                $char->gold -= $priceGold;
                $user->tokens -= $priceTokens;

                $char->save();
                $user->save();

                return (object)[
                    'status' => 1,
                    'tokens' => $user->tokens,
                    'golds' => $char->gold,
                    'newt' => $newt
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
