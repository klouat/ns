<?php

namespace App\Services\Amf\SpecialJouninExamService;

use App\Models\Character;
use App\Models\CharacterSpJouninProgress;

class PromotionService
{
    /**
     * promoteToSpecialJounin
     * Parameters: [sessionKey, charId, classSkill]
     */
    public function promoteToSpecialJounin($session_key, $char_id, $class_skill)
    {
        $char = Character::find($char_id);
        if (!$char) {
            return (object)['status' => 0, 'error' => 'Character not found.'];
        }

        $progress = CharacterSpJouninProgress::where('character_id', $char_id)->first();
        if (!$progress || $progress->current_stage <= 13) {
            return (object)['status' => 0, 'error' => 'You have not completed all stages of the Special Jounin Exam.'];
        }

        if (in_array($char->rank, ['Jounin', 'Tensai Jounin'])) {
            $char->rank = 'Tensai Special Jounin';
            $char->class = $class_skill;
            $char->save();

            // Awards from ActionScript:
            // updateSkills("skill_345", true)
            // addSet("set_588_" + Character.character_gender)
            // character_class = this.CLASS_SKILL_ARR[this.selected_class - 1]

            \App\Helpers\ItemHelper::addItem($char_id, "skill_345");
            \App\Helpers\ItemHelper::addItem($char_id, $class_skill);
            \App\Helpers\ItemHelper::addItem($char_id, "set_588_" . $char->gender);
            \App\Helpers\ItemHelper::addItem($char_id, "tokens_200");

            return (object)[
                'status' => 1,
                'result' => 'Congratulations! You are now a Tensai Special Jounin!'
            ];
        } else {
            return (object)[
                'status' => 2,
                'result' => 'You are not eligible for Special Jounin promotion or already promoted.'
            ];
        }
    }
}
