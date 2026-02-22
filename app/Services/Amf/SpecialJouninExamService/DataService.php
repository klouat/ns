<?php

namespace App\Services\Amf\SpecialJouninExamService;

use App\Models\Character;
use App\Models\CharacterSpJouninProgress;
use Illuminate\Support\Facades\Log;

class DataService
{
    /**
     * getData
     * Parameters: [sessionKey, charId]
     */
    public function getData($session_key, $char_id)
    {
        Log::info("SpecialJouninExam.getData called", ['char_id' => $char_id]);

        $char = Character::find($char_id);

        if (!$char) {
            return (object)[
                'status' => 0,
                'error' => 'Character not found.'
            ];
        }

        $progress = CharacterSpJouninProgress::firstOrCreate(
            ['character_id' => $char_id],
            ['current_stage' => 1]
        );

        Log::info("SpecialJouninExam progress found", ['char_id' => $char_id, 'current_stage' => $progress->current_stage]);

        $stages = [];
        
        // Character rank check
        $is_special_jounin = in_array($char->rank, ['Special Jounin', 'Tensai Special Jounin', 'Ninja Tutor', 'Senior Ninja Tutor', 'Sage']);
        $claimed = $is_special_jounin ? 1 : 0;

        // Special Jounin Exam has 13 sub-stages (6 stages total with chapters)
        for ($i = 1; $i <= 13; $i++) {
            $status = 0; // Locked

            if ($i < (int)$progress->current_stage) {
                $status = 2; // Completed
            } elseif ($i == (int)$progress->current_stage) {
                $status = 1; // In progress
            }

            $stage_data = [
                'status' => (int)$status,
                'id' => (int)$i
            ];

            if ($i == 1) {
                $stage_data['claimed'] = (int)$claimed;
            }

            // Return as associative array - AMF library usually handles this better for dynamic classes
            $stages[] = $stage_data;
        }

        Log::info("SpecialJouninExam.getData output", ['stages_json' => json_encode($stages)]);

        return (object)[
            'status' => 1,
            'data' => $stages
        ];
    }
}
