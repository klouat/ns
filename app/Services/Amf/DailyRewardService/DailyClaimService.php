<?php

namespace App\Services\Amf\DailyRewardService;

use App\Models\Character;
use App\Models\CharacterAttendance;
use App\Models\User;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyClaimService
{
    public function getDailyData($charId, $sessionKey)
    {
        try {
            $char = Character::find($charId);
            $today = now()->toDateString();
            
            $attendance = CharacterAttendance::where('character_id', $charId)
                ->orderBy('id', 'desc')
                ->first();

            if (!$attendance) {
                $attendance = CharacterAttendance::create([
                    'character_id' => $charId,
                    'month' => now()->month,
                    'year' => now()->year,
                    'attendance_days' => [],
                    'claimed_milestones' => []
                ]);
            }

            $user = User::find($char->user_id);

            return (object)[
                'status' => 1,
                'tokens' => ($attendance->last_token_claim && $attendance->last_token_claim->toDateString() === $today) ? false : true,
                'xp' => ($attendance->last_xp_claim && $attendance->last_xp_claim->toDateString() === $today) ? false : true,
                'timer' => 0,
                'scroll' => (bool)$user->claimed_scroll,
            ];
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.DailyClaimService.getDailyData: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
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
                    return (object)['status' => 2, 'result' => 'Already claimed today!'];
                }

                $attendance->last_token_claim = $today;
                $attendance->save();

                $user = User::find($char->user_id);
                if ($user) {
                    $user->tokens += 25;
                    $user->save();
                }

                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.DailyClaimService.getDailyTokenData: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
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
                    return (object)['status' => 2, 'result' => 'Already claimed today!'];
                }

                $attendance->last_xp_claim = $today;
                $attendance->save();

                $targetXp = ExperienceHelper::getRequiredCharacterXp($char->level);

                $min = (int)($targetXp * 0.10);
                $max = (int)($targetXp * 0.30);
                
                if ($min > $max) $min = $max;
                
                $rewardXP = mt_rand($min, $max);
                
                $leveledUp = ExperienceHelper::addCharacterXp($char, $rewardXP);
                $char->save();

                return (object)[
                    'status' => 1,
                    'reward' => $rewardXP,
                    'xp' => $char->xp,
                    'level_up' => $leveledUp
                ];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.DailyClaimService.claimDailyXP: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }

    public function claimScrollOfWisdom($charId, $sessionKey)
    {
        try {
            return DB::transaction(function () use ($charId) {
                $char = Character::lockForUpdate()->find($charId);
                if ($char->level < 80) return (object)['status' => 2, 'result' => 'Must be level 80!'];

                $user = User::lockForUpdate()->find($char->user_id);
                
                if ($user->claimed_scroll) {
                    return (object)['status' => 2, 'result' => 'Already claimed!'];
                }

                $user->claimed_scroll = true;
                $user->save();

                RewardHelper::addItem($charId, 'essential_10');

                return (object)['status' => 1];
            });
        } catch (\Exception $e) {
            Log::error("Error in DailyRewardService.DailyClaimService.claimScrollOfWisdom: " . $e->getMessage());
            return (object)['status' => 0, 'error' => 'Internal Server Error'];
        }
    }
}
