<?php

namespace App\Services\Amf\JouninExamService;

use App\Models\Character;
use App\Models\CharacterJouninProgress;
use Illuminate\Support\Facades\Log;

class StageService
{
    /**
     * startStage
     * Parameters: [sessionKey, charId, targetStage]
     */
    public function startStage($sessionKey, $charId, $targetStage)
    {
        // AS sends targetStage as int(this.target) + 5
        // Stage 1 (target=1) sends 6
        // Stage 2 (target=2) sends 7
        // ...
        // Stage 5 (target=5) sends 10
        
        $stageId = (int)$targetStage;
        
        if ($stageId < 6 || $stageId > 10) {
            return (object)['status' => 0, 'error' => 'Invalid stage selected.'];
        }

        // Map stage 6-10 back to 1-5 for progress tracking and client response
        $actualStage = $stageId - 5;

        $progress = CharacterJouninProgress::where('character_id', $charId)->first();
        
        if (!$progress) {
            $progress = CharacterJouninProgress::create([
                'character_id' => $charId,
                'current_stage' => 1
            ]);
        }

        // Sequential validation (optional but recommended)
        if ($actualStage > $progress->current_stage) {
            return (object)[
                'status' => 0, 
                'error' => "You must complete stage {$progress->current_stage} first."
            ];
        }

        return (object)[
            'status' => 1,
            'result' => "start_stage_" . $actualStage,
            'hash' => md5("jounin_stage_{$actualStage}" . time())
        ];
    }

    /**
     * finishStage
     * Parameters: [sessionKey, charId, stageIndex, isSuccess, ...]
     */
    public function finishStage($sessionKey, $charId, $stageIndex, $isSuccess = true)
    {
        $stageId = (int)$stageIndex;
        // Map 6-10 -> 1-5
        $actualStage = $stageId > 5 ? $stageId - 5 : $stageId;

        $progress = CharacterJouninProgress::where('character_id', $charId)->first();
        if (!$progress) {
            return (object)['status' => 0, 'error' => 'No progress found.'];
        }

        if ($isSuccess && $progress->current_stage == $actualStage) {
            $progress->current_stage++;
            $progress->save();

            return (object)[
                'status' => 1,
                'result' => "finish_stage_" . $actualStage,
                'hash' => md5("jounin_finish_{$actualStage}" . time())
            ];
        }

        return (object)[
            'status' => 1, // Return 1 even if already finished to avoid client errors
            'result' => "finish_stage_" . $actualStage
        ];
    }
}
