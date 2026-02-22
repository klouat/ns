<?php

namespace App\Services\Amf\FriendService;

class FriendHelperService
{
    public function formatFriendData($friendChar, $isFavorite = false)
    {
        $genderSuffix = ($friendChar->gender == 1) ? '_1' : '_0';
        
        $hairstyle = $friendChar->hair_style;
        if (is_numeric($hairstyle)) {
            $hairstyle = 'hair_' . str_pad($hairstyle, 2, '0', STR_PAD_LEFT) . $genderSuffix;
        } elseif ($hairstyle == null) {
            $hairstyle = 'hair_01' . $genderSuffix;
        }

        $rankId = match($friendChar->rank) {
            'Chunin' => 2,
            'Tensai Chunin' => 3,
            'Jounin' => 4,
            'Tensai Jounin' => 5,
            'Special Jounin' => 6,
            'Tensai Special Jounin' => 7,
            'Ninja Tutor' => 8,
            'Senior Ninja Tutor' => 9,
            default => 1
        };

        return (object)[
            'id' => $friendChar->id,
            'name' => $friendChar->name,
            'level' => (string)$friendChar->level,
            'rank' => $rankId,
            'element_1' => $friendChar->element_1,
            'element_2' => $friendChar->element_2,
            'element_3' => $friendChar->element_3,
            'emblem' => $friendChar->user && $friendChar->user->account_type == 1,
            'char' => (object)[
                'name' => $friendChar->name,
                'level' => $friendChar->level,
                'rank' => $rankId,
            ],
            'account_type' => $friendChar->user ? $friendChar->user->account_type : 0,
            'set' => (object)[
                'weapon' => $friendChar->equipment_weapon,
                'back_item' => $friendChar->equipment_back,
                'clothing' => $friendChar->equipment_clothing,
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'sets' => (object)[
                'hairstyle' => $hairstyle,
                'face' => 'face_01' . $genderSuffix,
                'hair_color' => $friendChar->hair_color ?: '0|0',
                'skin_color' => $friendChar->skin_color ?: '0|0',
            ],
            'is_favorite' => $isFavorite
        ];
    }
}
