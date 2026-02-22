<?php

namespace App\Services\Amf\JouninExamService;

use App\Models\Character;
use App\Models\CharacterJouninProgress;
use App\Helpers\ExperienceHelper;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    /**
     * promoteToJounin
     * Parameters: [sessionKey, charId]
     */
    public function promoteToJounin($sessionKey, $charId)
    {
        $char = Character::find($charId);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found.'];
        }

        $progress = CharacterJouninProgress::where('character_id', $charId)->first();
        if (!$progress || $progress->current_stage <= 5) {
            return (object)['status' => 0, 'error' => 'You have not completed all stages of the Jounin Exam.'];
        }

        if (in_array($char->rank, ['Tensai Chunin', 'Chunin'])) {
            $char->rank = 'Tensai Jounin';
            $char->save();

            // Rewards for Jounin promotion
            $rewards = [
                "wpn_47", // Example Jounin weapon
                "tokens_100"
            ];

            // In AS: makeJounin(param1.rewards)
            return (object)[
                'status' => 1,
                'rewards' => $rewards,
                'result' => 'Congratulations! You are now a Tensai Jounin!'
            ];
        } else {
            return (object)[
                'status' => 2,
                'result' => 'You are not eligible for Jounin promotion or already promoted.'
            ];
        }
    }
}
