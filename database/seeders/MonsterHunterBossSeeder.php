<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MonsterHunterBoss;

class MonsterHunterBossSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $bosses = [
            [
                'id' => ['ene_460'],
                'rewards' => ['wpn_1138' => 5, 'material_01' => 40, 'material_2110' => 40, 'item_58' => 30],
                'gold' => 30000,
                'xp' => 20000
            ],
            [
                'id' => ['ene_461'],
                'rewards' => ['wpn_1140' => 5, 'material_01' => 40, 'material_02' => 40, 'material_2110' => 30, 'item_58' => 20],
                'gold' => 35000,
                'xp' => 23000
            ],
            [
                'id' => ['ene_462'],
                'rewards' => ['wpn_1142' => 5, 'material_01' => 40, 'material_02' => 40, 'material_2110' => 30, 'item_58' => 20],
                'gold' => 38000,
                'xp' => 25000
            ],
            [
                'id' => ['ene_463', 'ene_464'],
                'rewards' => ['wpn_1144' => 5, 'wpn_1146' => 5, 'material_01' => 40, 'material_02' => 40, 'material_03' => 30, 'material_2110' => 20, 'item_58' => 20],
                'gold' => 40000,
                'xp' => 30000
            ],
            [
                'id' => ['ene_465'],
                'rewards' => ['wpn_1148' => 5, 'material_01' => 40, 'material_02' => 40, 'material_03' => 30, 'material_2110' => 20, 'item_58' => 20],
                'gold' => 49000,
                'xp' => 35000
            ],
            [
                'id' => ['ene_466'],
                'rewards' => ['wpn_1150' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_2110' => 20, 'item_58' => 20],
                'gold' => 55000,
                'xp' => 38000
            ],
            [
                'id' => ['ene_467'],
                'rewards' => ['wpn_1152' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_2110' => 20, 'item_58' => 20],
                'gold' => 57000,
                'xp' => 40000
            ],
            [
                'id' => ['ene_468'],
                'rewards' => ['wpn_1154' => 5, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_05' => 15, 'material_2110' => 15, 'item_58' => 15],
                'gold' => 62000,
                'xp' => 48000
            ],
            [
                'id' => ['ene_469'],
                'rewards' => ['wpn_1156' => 5, 'material_02' => 30, 'material_03' => 30, 'material_04' => 20, 'material_05' => 15, 'material_2110' => 15, 'item_58' => 15],
                'gold' => 72000,
                'xp' => 56000
            ],
            [
                'id' => ['ene_470'],
                'rewards' => ['wpn_1158' => 5, 'material_01' => 30, 'material_02' => 30, 'material_03' => 20, 'material_04' => 20, 'material_05' => 15, 'material_06' => 10, 'material_2110' => 15, 'item_58' => 15],
                'gold' => 100000,
                'xp' => 750000
            ],
            [
                'id' => ['ene_432', 'ene_433'],
                'rewards' => ['wpn_1111' => 2, 'wpn_1112' => 2, 'material_01' => 30, 'material_02' => 30, 'material_03' => 20, 'material_04' => 20, 'material_05' => 15, 'material_06' => 10, 'material_2110' => 15, 'item_58' => 15],
                'gold' => 150000,
                'xp' => 100000
            ]
        ];

        foreach ($bosses as $boss) {
            foreach ($boss['id'] as $bossId) {
                MonsterHunterBoss::updateOrCreate(
                    ['boss_id' => $bossId],
                    [
                        'xp' => $boss['xp'],
                        'gold' => $boss['gold'],
                        'rewards' => $boss['rewards']
                    ]
                );
            }
        }
    }
}
