<?php

namespace App\Services\Amf;

use App\Services\Amf\SpecialJouninExamService\DataService;
use App\Services\Amf\SpecialJouninExamService\PromotionService;
use App\Services\Amf\SpecialJouninExamService\StageService;

class SpecialJouninExamService
{
    private DataService $data_service;
    private StageService $stage_service;
    private PromotionService $promotion_service;

    public function __construct()
    {
        $this->data_service = new DataService();
        $this->stage_service = new StageService();
        $this->promotion_service = new PromotionService();
    }

    /**
     * getData
     * Parameters: [sessionKey, charId]
     */
    public function getData($session_key, $char_id)
    {
        return $this->data_service->getData($session_key, $char_id);
    }

    /**
     * startStage
     * Parameters: [sessionKey, charId, targetStage]
     */
    public function startStage($session_key, $char_id, $target_stage)
    {
        return $this->stage_service->startStage($session_key, $char_id, $target_stage);
    }

    /**
     * finishStage
     * Parameters: [sessionKey, charId, stageIndex, isSuccess, ...]
     */
    public function finishStage($session_key, $char_id, $stage_index, $is_success = true)
    {
        return $this->stage_service->finishStage($session_key, $char_id, $stage_index, $is_success);
    }

    /**
     * promoteToSpecialJounin
     * Parameters: [sessionKey, charId, classSkill]
     */
    public function promoteToSpecialJounin($session_key, $char_id, $class_skill)
    {
        return $this->promotion_service->promoteToSpecialJounin($session_key, $char_id, $class_skill);
    }
}
