<?php

namespace App\Services\Amf\ChuninExamService;

use App\Models\Character;
use App\Models\CharacterChuninProgress;
use App\Http\Controllers\AmfController;
use Illuminate\Support\Facades\Log;

class DataService
{
    /**
     * getData
     * Parameters: [sessionKey, charId]
     */
    public function getData($sessionKey, $charId)
    {
        // 1. Session and CharId validation if needed
        // Assuming AmfController or calling function handles basic checks, but for robustness:
        // $isValid = SystemLoginService::validateSession($sessionKey, $charId); 
        
        $char = Character::find($charId);

        if (!$char) {
            throw new \Exception("Character not found.");
        }

        // 2. Fetch or create progress
        $progress = CharacterChuninProgress::firstOrCreate(
            ['character_id' => $charId],
            [
                'current_stage' => 1,
                'stage_status' => 0, // 0: not started
            ]
        );

        // 3. Construct return data for AS
        $stages = [];
        $isChunin = $char->rank != 'Genin' && $char->rank != 'Academy Student'; 
        // Logic: specific ranks might be "Chunin" or higher. 
        // Simple check: if rank is anything other than Genin/Academy/null, we assume they claimed it.
        // Actually, let's use the DB progress. 
        // The AS checks chunin_data[0].claimed == "0" to show the claim button.
        
        $claimed = $isChunin ? "1" : "0";

        for ($i = 1; $i <= 5; $i++) {
            $status = "0"; // Locked/Default

            if ($i < $progress->current_stage) {
                // Completed stages
                $status = "2";
            } elseif ($i == $progress->current_stage) {
                // Current stage
                $status = "1";
            } else {
                // Future stages
                $status = "0";
            }

            $stageData = [
                'status' => $status,
                'id' => $i 
            ];

            // Attach 'claimed' property to the first element as expected by AS
            if ($i == 1) {
                $stageData['claimed'] = $claimed;
            }

            $stages[] = (object)$stageData;
        }

        Log::info("ChuninExam Data: " . json_encode($stages));

        return (object)[
            'status' => 1,
            'data' => $stages
        ];

    }
}
