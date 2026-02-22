<?php

namespace App\Services\Amf;

use App\Services\Amf\PvPService\CheckAccessService;
use App\Services\Amf\PvPService\PvPStatsService;
use App\Services\Amf\PvPService\PvPActivityService;
use App\Services\Amf\PvPService\PvPDetailService;
use App\Services\Amf\PvPService\PvPLeaderboardService;
use App\Services\Amf\PvPService\PvPReportService;

class PvPService
{
    private CheckAccessService $checkAccessService;
    private PvPStatsService $pvpStatsService;
    private PvPActivityService $pvpActivityService;
    private PvPDetailService $pvpDetailService;
    private PvPLeaderboardService $pvpLeaderboardService;
    private PvPReportService $pvpReportService;

    public function __construct()
    {
        $this->checkAccessService = new CheckAccessService();
        $this->pvpStatsService = new PvPStatsService();
        $this->pvpActivityService = new PvPActivityService();
        $this->pvpDetailService = new PvPDetailService();
        $this->pvpLeaderboardService = new PvPLeaderboardService();
        $this->pvpReportService = new PvPReportService();
    }

    public function checkAccess($char_id, $session_key)
    {
        return $this->checkAccessService->checkAccess($char_id, $session_key);
    }

    public function getCharacterStats($char_id, $session_key)
    {
        return $this->pvpStatsService->getCharacterStats($char_id, $session_key);
    }

    public function getBattleActivity($char_id, $session_key)
    {
        return $this->pvpActivityService->getBattleActivity($char_id, $session_key);
    }

    public function getDetailBattle($char_id, $session_key, $battle_id)
    {
        return $this->pvpDetailService->getDetailBattle($char_id, $session_key, $battle_id);
    }

    public function getLeaderboard($char_id, $session_key)
    {
        return $this->pvpLeaderboardService->getLeaderboard($char_id, $session_key);
    }

    public function reportBug($char_id, $session_key, $title, $description)
    {
        return $this->pvpReportService->reportBug($char_id, $session_key, $title, $description);
    }
}
