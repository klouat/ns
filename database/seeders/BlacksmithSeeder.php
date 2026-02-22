<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlacksmithSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('blacksmith_items')->truncate();

        $items = [

            // ===== BASIC =====
            ['wpn_609', ['material_01'], [1], 100, 1000, 'wpn_03'],
            ['wpn_119', ['material_01'], [5], 100, 10000, 'wpn_24'],
            ['wpn_179', ['material_01'], [2], 400, 10000, 'wpn_119'],
            ['wpn_164', ['material_01'], [3], 400, 10000, 'wpn_01'],

            // ===== MATERIAL 01 + 02 =====
            ['wpn_293', ['material_01','material_02'], [3,1], 500, 11000, 'wpn_23'],
            ['wpn_137', ['material_01','material_02'], [5,1], 600, 12000, 'wpn_12'],
            ['wpn_135', ['material_01','material_02'], [3,1], 300, 6500, 'wpn_06'],
            ['wpn_294', ['material_01','material_02'], [5,1], 500, 13000, 'wpn_36'],
            ['wpn_295', ['material_01','material_02'], [5,2], 500, 15000, 'wpn_50'],
            ['wpn_131', ['material_01','material_02'], [2,1], 100, 7500, 'wpn_111'],
            ['wpn_125', ['material_01','material_02'], [5,3], 500, 15000, 'wpn_39'],
            ['wpn_181', ['material_01','material_02'], [3,1], 200, 15000, 'wpn_125'],
            ['wpn_296', ['material_01','material_02'], [7,2], 600, 17000, 'wpn_31'],
            ['wpn_140', ['material_01','material_02'], [6,3], 600, 18000, 'wpn_16'],
            ['wpn_297', ['material_01','material_02'], [8,3], 600, 19000, 'wpn_47'],

            // ===== MATERIAL 01 + 02 + 03 =====
            ['wpn_303', ['material_01','material_02','material_03'], [5,3,1], 400, 2000, 'wpn_301'],
            ['wpn_136', ['material_01','material_02','material_03'], [5,1,1], 300, 10000, 'wpn_131'],
            ['wpn_139', ['material_01','material_02','material_03'], [3,2,1], 300, 10000, 'wpn_37'],
            ['wpn_304', ['material_01','material_02','material_03'], [5,3,1], 400, 2000, 'wpn_301'],
            ['wpn_305', ['material_01','material_02','material_03'], [5,3,1], 400, 2000, 'wpn_301'],
            ['wpn_138', ['material_01','material_02','material_03'], [3,2,1], 300, 10000, 'wpn_07'],
            ['wpn_126', ['material_01','material_02','material_03'], [5,3,1], 800, 21000, 'wpn_42'],
            ['wpn_134', ['material_01','material_02','material_03'], [4,4,1], 400, 11000, 'wpn_05'],
            ['wpn_298', ['material_01','material_02','material_03'], [6,3,2], 800, 23000, 'wpn_44'],
            ['wpn_142', ['material_01','material_02','material_03'], [5,2,2], 300, 25000, 'wpn_25'],
            ['wpn_127', ['material_01','material_02','material_03'], [5,3,2], 800, 25000, 'wpn_40'],
            ['wpn_144', ['material_01','material_02','material_03'], [5,4,2], 600, 13500, 'wpn_85'],

            // ===== MATERIAL 01–04 =====
            ['wpn_611', ['material_01','material_02','material_03','material_04'], [4,3,2,1], 400, 15000, 'wpn_610'],
            ['wpn_307', ['material_01','material_02','material_03','material_04'], [5,3,3,1], 600, 3000, 'wpn_302'],
            ['wpn_306', ['material_01','material_02','material_03','material_04'], [5,3,3,1], 600, 3000, 'wpn_302'],
            ['wpn_299', ['material_01','material_02','material_03','material_04'], [5,3,3,1], 800, 30000, 'wpn_136'],
            ['wpn_128', ['material_01','material_02','material_03','material_04'], [8,5,3,1], 800, 30000, 'wpn_56'],
            ['wpn_291', ['material_01','material_02','material_03','material_04'], [8,5,3,2], 800, 33000, 'wpn_59'],
            ['wpn_130', ['material_01','material_02','material_03','material_04'], [10,6,4,3], 800, 34000, 'wpn_79'],
            ['wpn_148', ['material_01','material_02','material_03','material_04'], [3,2,2,1], 800, 35000, 'wpn_86'],

            // ===== MATERIAL 01–05 =====
            ['wpn_129', ['material_01','material_02','material_03','material_04','material_05'], [10,8,6,3,1], 800, 35000, 'wpn_54'],
            ['wpn_292', ['material_01','material_02','material_03','material_04','material_05'], [9,6,4,5,1], 800, 42000, 'wpn_88'],
            ['wpn_150', ['material_01','material_02','material_03','material_04','material_05'], [4,3,1,1,1], 300, 25000, 'wpn_46'],
            ['wpn_612', ['material_01','material_02','material_03','material_04','material_05'], [4,3,1,1,1], 300, 25000, 'wpn_611'],

            // ===== ENDGAME =====
            ['wpn_1139', ['material_01','material_02'], [3,2], 1000, 1000000, 'wpn_1138'],
            ['wpn_1141', ['material_01','material_02'], [5,4], 2000, 2000000, 'wpn_1140'],
            ['wpn_1143', ['material_01','material_02','material_03'], [7,5,3], 3000, 3000000, 'wpn_1142'],
            ['wpn_1145', ['material_01','material_02','material_03'], [15,13,7], 4000, 4000000, 'wpn_1144'],
            ['wpn_1147', ['material_01','material_02','material_03'], [15,13,7], 4000, 4000000, 'wpn_1146'],
            ['wpn_1149', ['material_01','material_02','material_03'], [24,17,12], 5000, 5000000, 'wpn_1148'],
            ['wpn_1151', ['material_01','material_02','material_03','material_04'], [30,20,15,6], 6000, 6000000, 'wpn_1150'],
            ['wpn_1153', ['material_01','material_02','material_03','material_04'], [35,27,23,15], 7000, 7000000, 'wpn_1152'],
            ['wpn_1155', ['material_01','material_02','material_03','material_04','material_05'], [40,33,25,15,3], 8000, 8000000, 'wpn_1154'],
            ['wpn_1157', ['material_01','material_02','material_03','material_04','material_05'], [45,35,27,18,5], 9000, 9000000, 'wpn_1156'],
            ['wpn_1159', ['material_01','material_02','material_03','material_04','material_05','material_06'], [50,40,30,20,10,1], 10000, 10000000, 'wpn_1158'],
        ];

        foreach ($items as [$id, $mats, $qtys, $token, $gold, $req]) {
            DB::table('blacksmith_items')->insert([
                'item_id'     => $id,
                'materials'   => json_encode($mats),
                'quantities'  => json_encode($qtys),
                'token_price' => $token,
                'gold_price'  => $gold,
                'req_weapon'  => $req,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
