<?php

namespace App\Services\Amf\JouninExamService;

use App\Models\Character;
use App\Models\CharacterJouninProgress;
use Illuminate\Support\Facades\Log;

class DataService
{
    /**
     * getData
     * Parameters: [sessionKey, charId]
     */
    public function getData($sessionKey, $charId)
    {
        $char = Character::find($charId);

        if (!$char) {
            return (object)[
                'status' => 0,
                'error' => 'Character not found.'
            ];
        }

        $progress = CharacterJouninProgress::firstOrCreate(
            ['character_id' => $charId],
            ['current_stage' => 1]
        );

        $stages = [];
        // Ranks can be 'Jounin', 'Tensai Special Jounin', etc.
        // We'll check if the character is already Jounin or higher.
        $isJounin = in_array($char->rank, ['Jounin', 'Tensai Special Jounin', 'Senior Ninja Tutor', 'Sage']);
        $claimed = $isJounin ? "1" : "0";

        for ($i = 1; $i <= 5; $i++) {
            $status = "0"; // Locked

            if ($i < $progress->current_stage) {
                $status = "2"; // Completed
            } elseif ($i == $progress->current_stage) {
                $status = "1"; // In progress
            }

            $stageData = [
                'status' => $status,
                'id' => $i
            ];

            if ($i == 1) {
                $stageData['claimed'] = $claimed;
            }

            $stages[] = (object)$stageData;
        }

        return (object)[
            'status' => 1,
            'data' => $stages
        ];
    }
}
