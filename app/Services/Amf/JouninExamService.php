<?php

namespace App\Services\Amf;

use App\Services\Amf\JouninExamService\DataService;
use App\Services\Amf\JouninExamService\PromotionService;
use App\Services\Amf\JouninExamService\StageService;

class JouninExamService
{
    private DataService $dataService;
    private StageService $stageService;
    private PromotionService $promotionService;

    public function __construct()
    {
        $this->dataService = new DataService();
        $this->stageService = new StageService();
        $this->promotionService = new PromotionService();
    }

    /**
     * getData
     * Parameters: [sessionKey, charId]
     */
    public function getData($sessionKey, $charId)
    {
        return $this->dataService->getData($sessionKey, $charId);
    }

    /**
     * startStage
     * Parameters: [sessionKey, charId, targetStage]
     */
    public function startStage($sessionKey, $charId, $targetStage)
    {
        return $this->stageService->startStage($sessionKey, $charId, $targetStage);
    }

    /**
     * finishStage
     * Parameters: [sessionKey, charId, stageIndex, isSuccess, ...]
     */
    public function finishStage($sessionKey, $charId, $stageIndex, $isSuccess = true)
    {
        return $this->stageService->finishStage($sessionKey, $charId, $stageIndex, $isSuccess);
    }

    /**
     * promoteToJounin
     * Parameters: [sessionKey, charId]
     */
    public function promoteToJounin($sessionKey, $charId)
    {
        return $this->promotionService->promoteToJounin($sessionKey, $charId);
    }
}
