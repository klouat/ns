<?php

namespace App\Services\Amf\FriendService;

use App\Models\Character;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BattleService
{
    public function startBerantem($charId, $friendId, $hash, $sessionKey)
    {
        $battleCode = bin2hex(random_bytes(16));
        
        Cache::put('battle_friend_' . $charId, [
            'code' => $battleCode,
            'friend_id' => $friendId,
            'timestamp' => now()
        ], 600);

        return (object)[
            'status' => 1,
            'battle_code' => $battleCode,
            'friend_id' => $friendId
        ];
    }

    public function endBerantem($charId, $battleCode, $hash, $sessionKey, $logs)
    {
        try {
            return DB::transaction(function () use ($charId, $battleCode, $hash, $sessionKey) {
                $char = Character::lockForUpdate()->find($charId);
                if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

                $expectedHash = hash('sha256', (string)$charId . (string)$battleCode . (string)$sessionKey);
                
                if ($hash !== $expectedHash) {
                    Log::warning("Battle hash mismatch for character $charId. Expected: $expectedHash, got: $hash");
                }

                $goldGain = 100;
                $xpGain = 50;

                $char->gold += $goldGain;
                $char->xp += $xpGain;

                $kunaiGained = false;
                $battleInfo = Cache::get('battle_friend_' . $charId);
                
                if ($battleInfo && $battleInfo['code'] === $battleCode) {
                    $friendChar = Character::find($battleInfo['friend_id']);
                    if ($friendChar) {
                        $levelDiff = abs($char->level - $friendChar->level);
                        
                        if ($levelDiff <= 10) {
                            $today = now()->format('Y-m-d');
                            $dailyKey = "daily_kunai_{$charId}_{$today}";
                            $dailyCount = Cache::get($dailyKey, 0);

                            if ($dailyCount < 10) {
                                \App\Helpers\ItemHelper::addItem($char->id, 'material_1002', 1);
                                Cache::increment($dailyKey);
                                $kunaiGained = true;
                            }
                        }
                    }
                }
                
                Cache::forget('battle_friend_' . $charId);
                
                $levelUp = false;
                $xpNext = $char->level * 100;
                if ($char->xp >= $xpNext) {
                    // $char->level++;
                    // $levelUp = true;
                }

                $char->save();
                
                $droppedItems = [];
                if ($kunaiGained) {
                    $droppedItems[] = 'material_1002';
                }

                return (object)[
                    'status' => 1,
                    'gold' => $char->gold,
                    'xp' => $char->xp,
                    'level' => $char->level,
                    'level_up' => $levelUp,
                    'result' => [
                        (string)$goldGain,
                        (string)$xpGain,
                        $droppedItems
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error($e);
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
