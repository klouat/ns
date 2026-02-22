<?php

namespace App\Services\Amf\ConfrontingDeathEvent2025Service;

class RewardHelper
{
    public static $bossData = [
        'id' => 'ene_2120',
        'levels' => [0, 5],
        'gold' => "level * 2500 / 60",
        'xp' => "level * 2500 / 60",
        'rewards' => [
            "material_2232",
            "material_2233",
            "material_2234"
        ]
    ];

    public static $milestoneData = [
         ['id' => 'gold_100000', 'requirement' => 10, 'quantity' => 1],
         ['id' => 'material_2233', 'requirement' => 50, 'quantity' => 10],
         ['id' => 'hair_2375_%s', 'requirement' => 100, 'quantity' => 1],
         ['id' => 'essential_05', 'requirement' => 200, 'quantity' => 5],
         ['id' => 'set_2416_%s', 'requirement' => 300, 'quantity' => 1],
         ['id' => 'tokens_150', 'requirement' => 400, 'quantity' => 1],
         ['id' => 'back_2408', 'requirement' => 600, 'quantity' => 1],
         ['id' => 'wpn_2410', 'requirement' => 750, 'quantity' => 1]
    ];
}
