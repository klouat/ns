<?php

namespace App\Services\Amf;

use App\Services\Amf\DailyRewardService\AttendanceService;
use App\Services\Amf\DailyRewardService\DailyClaimService;
use App\Services\Amf\DailyRewardService\GradeSSpinService;

class DailyRewardService
{
    private AttendanceService $attendanceService;
    private DailyClaimService $dailyClaimService;
    private GradeSSpinService $gradeSSpinService;

    public function __construct()
    {
        $this->attendanceService = new AttendanceService();
        $this->dailyClaimService = new DailyClaimService();
        $this->gradeSSpinService = new GradeSSpinService();
    }

    public function getAttendances($charId, $sessionKey)
    {
        return $this->attendanceService->getAttendances($charId, $sessionKey);
    }

    public function claimAttendanceReward($charId, $sessionKey, $rewardId)
    {
        return $this->attendanceService->claimAttendanceReward($charId, $sessionKey, $rewardId);
    }

    public function getDailyData($charId, $sessionKey)
    {
        return $this->dailyClaimService->getDailyData($charId, $sessionKey);
    }

    public function getDailyTokenData($charId, $sessionKey)
    {
        return $this->dailyClaimService->getDailyTokenData($charId, $sessionKey);
    }

    public function claimDailyXP($charId, $sessionKey)
    {
        return $this->dailyClaimService->claimDailyXP($charId, $sessionKey);
    }

    public function claimDoubleXP($charId, $sessionKey)
    {
        return $this->dailyClaimService->claimDailyXP($charId, $sessionKey);
    }

    public function claimScrollOfWisdom($charId, $sessionKey)
    {
        return $this->dailyClaimService->claimScrollOfWisdom($charId, $sessionKey);
    }

    public function getMissionGradeSSpin($charId, $sessionKey, $action = 'check')
    {
        return $this->gradeSSpinService->getMissionGradeSSpin($charId, $sessionKey);
    }

    public function getRewardMissionGradeS($charId, $sessionKey)
    {
        return $this->gradeSSpinService->getRewardMissionGradeS($charId, $sessionKey);
    }
}
