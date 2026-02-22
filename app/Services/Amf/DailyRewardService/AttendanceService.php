<?php

namespace App\Services\Amf\DailyRewardService;

use App\Models\AttendanceReward;
use App\Models\Character;
use App\Models\CharacterAttendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    public function getAttendances($charId, $sessionKey)
    {
        try {
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

            $mappedItems = array_map(function($item) { return (object)$item; }, $stampRewards->toArray());

            return (object)[
                'status' => 1,
                'items' => $mappedItems,
                'count' => $count,
                'attendances' => $days,
                'rewards' => $rewardsStatus
            ];
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.AttendanceService.getAttendances: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
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

                if (!$attendance) return (object)['status' => 0, 'error' => 'Attendance record not found'];

                $milestone = AttendanceReward::find($rewardId);

                if (!$milestone) return (object)['status' => 2, 'result' => 'Invalid reward ID'];
                
                $claimed = $attendance->claimed_milestones ?? [];
                if (in_array($rewardId, $claimed)) return (object)['status' => 2, 'result' => 'Already claimed!'];

                $count = count($attendance->attendance_days ?? []);
                if ($count < $milestone->price) return (object)['status' => 2, 'result' => 'Not enough attendance days!'];

                $claimed[] = (int)$rewardId;
                $attendance->claimed_milestones = $claimed;
                $attendance->save();

                RewardHelper::applyRewardString($char, $milestone->item);

                $stampRewards = AttendanceReward::orderBy('price')->get();
                $rewardsStatus = [];
                foreach ($stampRewards as $index => $m) {
                    $rewardsStatus[$index] = in_array($m->id, $claimed) ? 1 : 0;
                }

                return (object)[
                    'status' => 1,
                    'rewards' => $rewardsStatus,
                    'reward' => str_replace('~', '', $milestone->item),
                    'level_up' => false,
                    'xp' => $char->xp
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.AttendanceService.claimAttendanceReward: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
