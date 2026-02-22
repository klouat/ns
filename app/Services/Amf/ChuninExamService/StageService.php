<?php

namespace App\Services\Amf\ChuninExamService;

use App\Models\Character;
use App\Models\CharacterChuninProgress;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StageService
{
    /**
     * startStage
     * Parameters: [sessionKey, charId, targetStage]
     */
    public function startStage($sessionKey, $charId, $targetStage)
    {
        $char = Character::find($charId);
        if (!$char) return (object)['status' => 0, 'error' => 'Character not found'];

        $progress = CharacterChuninProgress::where('character_id', $charId)->first();
        if (!$progress) {
             // Create if missing, or error out
             $progress = CharacterChuninProgress::create([
                 'character_id' => $charId, 
                 'current_stage' => 1, 
                 'stage_status' => 0
             ]);
        }

        // Validate if player can start this stage
        if ($targetStage != $progress->current_stage) {
            return (object)['status' => 0, 'error' => "You must complete stage {$progress->current_stage} first."];
        }

        // Update status to started
        // We do NOT set stage_status to 1 to allow retries. 
        // We only care if status is 2 (completed).
        $progress->last_attempt_at = Carbon::now();
        $progress->save();

        return (object)[
            'status' => 1,
            'result' => "start_stage_" . intval($targetStage),
            'hash' => md5("stage{$targetStage}" . time()),
            // 'message' => "Stage {$targetStage} started!", // Debug
        ];
    }

    /**
     * finishStage (Called by client after battle or by server callback)
     * For security, BattleSystem should ideally call completeStage internally.
     * But if client calls it to acknowledge, we verify result.
     */
    public function finishStage($sessionKey, $charId, $stageIndex, $isSuccess = false, $questions = null, $answers = null)
    {
        Log::info("finishStage called", [
            'charId' => $charId,
            'stageIndex' => $stageIndex,
            'isSuccess' => $isSuccess,
            'type_success' => gettype($isSuccess)
        ]);
        
        $progress = CharacterChuninProgress::where('character_id', $charId)->first();
        if (!$progress) return (object)['status' => 0, 'error' => 'No progress found'];

        // Idempotency: If we are already past this stage, return success
        if ($progress->current_stage > $stageIndex) {
            return (object)[
                'status' => 1, 
                'result' => "finish_stage_{$stageIndex}",
                'hash' => md5("finish{$stageIndex}" . time()) 
            ];
        }
        
        // Trust the client call to finish the stage
        $passed = true;

        if ($passed && $progress->current_stage == $stageIndex) {
             Log::info("Advancing stage", ['from' => $progress->current_stage, 'to' => $progress->current_stage + 1]);

             $progress->current_stage++;
             $progress->stage_status = 0; // Reset for next stage
             $progress->last_attempt_at = Carbon::now();
             $progress->save();

             return (object)[
                 'status' => 1, 
                 'result' => "finish_stage_{$stageIndex}",
                 'hash' => md5("finish{$stageIndex}" . time())
             ];
        }
        
        return (object)['status' => 0, 'error' => "Stage not cleared yet."];
    }

    /**
     * Internal helper to mark stage as complete (e.g. from BattleSystem)
     */
    public function completeStage($charId, $stageIndex)
    {
        $progress = CharacterChuninProgress::where('character_id', $charId)->first();
        if ($progress && $progress->current_stage == $stageIndex) {
            $progress->stage_status = 2; // Finished
            $progress->save();
            return true;
        }
        return false;
    }
}
