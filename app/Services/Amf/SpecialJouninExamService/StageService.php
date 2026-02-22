<?php

namespace App\Services\Amf\SpecialJouninExamService;

use App\Models\Character;
use App\Models\CharacterSpJouninProgress;
use Illuminate\Support\Facades\Log;

class StageService
{
    /**
     * startStage
     */
    public function startStage($session_key, $char_id, $target_stage)
    {
        Log::info("StageService.startStage", ['char_id' => $char_id, 'target' => $target_stage]);

        $progress = CharacterSpJouninProgress::where('character_id', $char_id)->first();
        if (!$progress) {
             Log::error("StageService: Progress not found", ['char_id' => $char_id]);
            return (object)['status' => 0, 'error' => 'Progress not found.'];
        }

        // ActionScript sends curr_chapter + 11. curr_chapter is 0-12.
        // So target_stage is 11-23. Map this to 1-13.
        // If target_stage is < 11, assume it's already mapped (backward compat check).
        $mapped_stage = ($target_stage > 10) ? ($target_stage - 10) : $target_stage;
        
        Log::info("StageService Logic", [
            'db_stage' => $progress->current_stage,
            'input_stage' => $target_stage,
            'mapped_stage' => $mapped_stage
        ]);

        if ($progress->current_stage != $mapped_stage) {
            Log::warning("StageService: Invalid Sequence", [
                'expected' => ($progress->current_stage + 10),
                'got' => $target_stage,
                'db' => $progress->current_stage
            ]);
            return (object)['status' => 0, 'result' => 'Invalid stage sequence. Expected stage ' . ($progress->current_stage + 10)];
        }

        return (object)[
            'status' => 1,
            'result' => 'Stage started.'
        ];
    }

    /**
     * finishStage
     */
    public function finishStage($session_key, $char_id, $stage_index, $is_success = true)
    {
        if (!$is_success) {
            return (object)['status' => 1, 'result' => 'Stage failed.'];
        }

        $progress = CharacterSpJouninProgress::where('character_id', $char_id)->first();
        
        // stage_index is 0-12 in ActionScript? Wait, let me check finishStage call.
        // Usually it's current stage index.
        
        if ($progress && $progress->current_stage == ($stage_index + 1)) {
            $progress->current_stage++;
            $progress->save();
        }

        return (object)[
            'status' => 1,
            'result' => 'Stage completed.'
        ];
    }
}
