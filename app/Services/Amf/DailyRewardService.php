<?php

namespace App\Services\Amf;

use App\Models\AttendanceReward;
use App\Models\Character;
use App\Models\CharacterAttendance;
use App\Models\CharacterItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyRewardService
{
    public function getAttendances($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $now = now();
                $month = $now->month;
                $year = $now->year;
                $todayDay = $now->day;

                $attendance = CharacterAttendance::firstOrCreate(
                    ['character_id' => $charId, 'month' => $month, 'year' => $year],
                    ['attendance_days' => [], 'claimed_milestones' => []]
                );

                $days = $attendance->attendance_days ?? [];
                if (!in_array($todayDay, $days)) {
                    $days[] = $todayDay;
                    $attendance->attendance_days = $days;
                    $attendance->save();
                }

                $count = count($days);
                $claimed = $attendance->claimed_milestones ?? [];
                
                $stampRewards = AttendanceReward::orderBy('price')->get();
                $rewardsStatus = [];
                foreach ($stampRewards as $index => $milestone) {
                    $rewardsStatus[$index] = in_array($milestone->id, $claimed) ? 1 : 0;
                }

                return [
                    'status' => 1,
                    'items' => $stampRewards->toArray(),
                    'count' => $count,
                    'attendances' => $days,
                    'rewards' => $rewardsStatus
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.getAttendances: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claimAttendanceReward($charId, $sessionKey, $rewardId)
    {
        try {
            return DB::transaction(function () use ($charId, $rewardId) {
                $char = Character::lockForUpdate()->find($charId);
                $now = now();
                $attendance = CharacterAttendance::where('character_id', $charId)
                    ->where('month', $now->month)
                    ->where('year', $now->year)
                    ->lockForUpdate()
                    ->first();

                if (!$attendance) return ['status' => 0, 'error' => 'Attendance record not found'];

                $milestone = AttendanceReward::find($rewardId);

                if (!$milestone) return ['status' => 2, 'result' => 'Invalid reward ID'];
                
                $claimed = $attendance->claimed_milestones ?? [];
                if (in_array($rewardId, $claimed)) return ['status' => 2, 'result' => 'Already claimed!'];

                $count = count($attendance->attendance_days ?? []);
                if ($count < $milestone->price) return ['status' => 2, 'result' => 'Not enough attendance days!'];

                $claimed[] = (int)$rewardId;
                $attendance->claimed_milestones = $claimed;
                $attendance->save();

                $this->applyRewardString($char, $milestone->item);

                $stampRewards = AttendanceReward::orderBy('price')->get();
                $rewardsStatus = [];
                foreach ($stampRewards as $index => $m) {
                    $rewardsStatus[$index] = in_array($m->id, $claimed) ? 1 : 0;
                }

                return [
                    'status' => 1,
                    'rewards' => $rewardsStatus,
                    'reward' => $milestone->item,
                    'level_up' => false, // Simplified
                    'xp' => $char->xp
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.claimAttendanceReward: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error: ' . $e->getMessage()];
        }
    }

    public function getDailyData($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            $today = now()->toDateString();
            
            $attendance = CharacterAttendance::where('character_id', $charId)
                ->orderBy('id', 'desc') // Get latest one (usually current month)
                ->first();

            if (!$attendance) {
                // Should have been created by getAttendances but just in case
                $attendance = CharacterAttendance::create([
                    'character_id' => $charId,
                    'month' => now()->month,
                    'year' => now()->year,
                    'attendance_days' => [],
                    'claimed_milestones' => []
                ]);
            }

            $user = User::find($char->user_id);
            $isEmblem = $user && ($user->account_type == 1 || $user->emblem_duration > 0 || $user->emblem_duration == -1);

            return [
                'status' => 1,
                'tokens' => ($attendance->last_token_claim && $attendance->last_token_claim->toDateString() === $today) ? false : true,
                'xp' => ($attendance->last_xp_claim && $attendance->last_xp_claim->toDateString() === $today) ? false : true,
                'timer' => 0, // Not used much in simple logic
                'scroll' => ($attendance->last_scroll_claim && $attendance->last_scroll_claim->toDateString() === $today) ? true : false,
            ];
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.getDailyData: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function getDailyTokenData($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                $today = now()->toDateString();
                
                $attendance = CharacterAttendance::where('character_id', $charId)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                if ($attendance->last_token_claim && $attendance->last_token_claim->toDateString() === $today) {
                    return ['status' => 2, 'result' => 'Already claimed today!'];
                }

                $attendance->last_token_claim = $today;
                $attendance->save();

                $user = User::find($char->user_id);
                if ($user) {
                    $user->tokens += 25;
                    $user->save();
                }

                return ['status' => 1];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.getDailyTokenData: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claimDailyXP($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                $today = now()->toDateString();
                
                $attendance = CharacterAttendance::where('character_id', $charId)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                if ($attendance->last_xp_claim && $attendance->last_xp_claim->toDateString() === $today) {
                    return ['status' => 2, 'result' => 'Already claimed today!'];
                }

                $attendance->last_xp_claim = $today;
                $attendance->save();

                $rewardXP = $char->level * 500; // Sample formula
                $char->xp += $rewardXP;
                $char->save();

                return [
                    'status' => 1,
                    'reward' => $rewardXP,
                    'xp' => $char->xp,
                    'level_up' => false
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.claimDailyXP: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claimDoubleXP($charId, $sessionKey)
    {
        // This usually requires higher rank or tokens?
        // Let's just claim normal XP but maybe more?
        return $this->claimDailyXP($charId, $sessionKey);
    }

    public function claimScrollOfWisdom($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                if ($char->level < 80) return ['status' => 2, 'result' => 'Must be level 80!'];

                $today = now()->toDateString();
                $attendance = CharacterAttendance::where('character_id', $charId)
                    ->orderBy('id', 'desc')
                    ->lockForUpdate()
                    ->first();

                if ($attendance->last_scroll_claim && $attendance->last_scroll_claim->toDateString() === $today) {
                    return ['status' => 2, 'result' => 'Already claimed today!'];
                }

                $attendance->last_scroll_claim = $today;
                $attendance->save();

                $this->addItem($charId, 'essential_10');

                return ['status' => 1];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyReward.claimScrollOfWisdom: " . $e->getMessage());
            return ['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    private function applyRewardString($char, $rewardStr)
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
            $this->addItem($char->id, $rewardStr);
        }
    }

    private function addItem($charId, $itemId)
    {
        $category = 'item';
        if (str_starts_with($itemId, 'wpn_')) $category = 'weapon';
        elseif (str_starts_with($itemId, 'back_')) $category = 'back';
        elseif (str_starts_with($itemId, 'set_')) $category = 'set';
        elseif (str_starts_with($itemId, 'hair_')) $category = 'hair';
        elseif (str_starts_with($itemId, 'material_')) $category = 'material';
        elseif (str_starts_with($itemId, 'essential_')) $category = 'essential';
        elseif (str_starts_with($itemId, 'accessory_')) $category = 'accessory';

        $item = CharacterItem::where('character_id', $charId)
            ->where('item_id', $itemId)
            ->first();

        if ($item) {
            $item->quantity += 1;
            $item->save();
        } else {
            CharacterItem::create([
                'character_id' => $charId,
                'item_id' => $itemId,
                'quantity' => 1,
                'category' => $category
            ]);
        }
    }
}
