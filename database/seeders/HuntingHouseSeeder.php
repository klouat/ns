<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HuntingHouseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('hunting_house_items')->truncate();

        $items = [
            ['item_id' => 'wpn_823', 'materials' => ['wpn_822','material_600','material_601','material_602','material_603','material_604','material_605'], 'quantities' => [1,1,1,1,1,2,2]],
            ['item_id' => 'wpn_825', 'materials' => ['wpn_824','material_600','material_601','material_602','material_603','material_605','material_606','material_607'], 'quantities' => [1,3,3,2,2,5,5,3]],
            ['item_id' => 'wpn_826', 'materials' => ['wpn_825','material_600','material_601','material_602','material_603','material_605','material_606','material_607'], 'quantities' => [1,5,5,3,3,10,10,4]],
            ['item_id' => 'wpn_827', 'materials' => ['material_604','material_605','material_608','material_609','material_610'], 'quantities' => [3,3,2,2,1]],
            ['item_id' => 'wpn_828', 'materials' => ['wpn_827','material_604','material_605','material_607','material_608','material_609','material_610'], 'quantities' => [1,5,5,2,3,3,2]],
            ['item_id' => 'wpn_829', 'materials' => ['wpn_828','material_605','material_606','material_607','material_608','material_609','material_610'], 'quantities' => [1,8,8,4,5,5,3]],
            ['item_id' => 'wpn_830', 'materials' => ['wpn_829','material_605','material_606','material_607','material_608','material_609','material_610'], 'quantities' => [1,12,12,7,7,7,4]],
            ['item_id' => 'wpn_831', 'materials' => ['material_624','material_625','material_626','material_627','material_629','material_630'], 'quantities' => [3,3,2,5,5,2]],
            ['item_id' => 'wpn_832', 'materials' => ['wpn_831','material_624','material_625','material_626','material_628','material_629','material_630'], 'quantities' => [1,5,5,3,8,8,4]],
            ['item_id' => 'wpn_833', 'materials' => ['wpn_832','material_624','material_625','material_626','material_628','material_629','material_630'], 'quantities' => [1,7,7,4,12,12,7]],
            ['item_id' => 'wpn_834', 'materials' => ['material_614','material_633'], 'quantities' => [2,2]],
            ['item_id' => 'wpn_835', 'materials' => ['wpn_834','material_614','material_631','material_632','material_633'], 'quantities' => [1,4,1,1,6]],
            ['item_id' => 'wpn_837', 'materials' => ['wpn_836','material_615','material_616','material_631','material_632','material_633'], 'quantities' => [1,10,3,2,2,16]],
            ['item_id' => 'wpn_838', 'materials' => ['wpn_837','material_615','material_616','material_631','material_632','material_633'], 'quantities' => [1,12,4,3,3,22]],
            ['item_id' => 'wpn_839', 'materials' => ['material_604','material_605','material_607','material_622','material_623'], 'quantities' => [5,5,2,6,2]],
            ['item_id' => 'wpn_840', 'materials' => ['wpn_839','material_605','material_606','material_607','material_622','material_623'], 'quantities' => [1,8,8,3,10,3]],
            ['item_id' => 'wpn_841', 'materials' => ['wpn_840','material_605','material_606','material_607','material_622','material_623'], 'quantities' => [1,12,12,5,14,4]],
            ['item_id' => 'wpn_842', 'materials' => ['material_618','material_633'], 'quantities' => [2,2]],
            ['item_id' => 'wpn_843', 'materials' => ['wpn_842','material_617','material_618','material_632','material_633'], 'quantities' => [1,1,4,1,6]],
            ['item_id' => 'wpn_844', 'materials' => ['wpn_843','material_617','material_618','material_631','material_632','material_633'], 'quantities' => [1,2,6,1,1,10]],
            ['item_id' => 'wpn_845', 'materials' => ['wpn_844','material_617','material_618','material_631','material_632','material_633'], 'quantities' => [1,3,10,2,2,16]],
            ['item_id' => 'wpn_846', 'materials' => ['wpn_845','material_617','material_618','material_631','material_632','material_633'], 'quantities' => [1,4,12,3,3,22]],
            ['item_id' => 'wpn_847', 'materials' => ['material_611','material_612','material_633'], 'quantities' => [1,1,2]],
            ['item_id' => 'wpn_848', 'materials' => ['wpn_847','material_611','material_612','material_613','material_632','material_633'], 'quantities' => [1,2,2,1,1,6]],
            ['item_id' => 'wpn_849', 'materials' => ['wpn_848','material_611','material_612','material_613','material_632','material_633'], 'quantities' => [1,3,3,2,1,10]],
            ['item_id' => 'wpn_854', 'materials' => ['wpn_853','material_605','material_606','material_607','material_619','material_620','material_621'], 'quantities' => [1,8,8,4,5,5,3]],
            ['item_id' => 'wpn_855', 'materials' => ['wpn_854','material_605','material_606','material_607','material_619','material_620','material_621'], 'quantities' => [1,12,12,7,7,7,4]],
        ];

        $now    = now();
        $insert = [];
        foreach ($items as $order => $row) {
            $insert[] = [
                'item_id'    => $row['item_id'],
                'category'   => 'wpn',
                'materials'  => json_encode($row['materials']),
                'quantities' => json_encode($row['quantities']),
                'sort_order' => $order + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('hunting_house_items')->insert($insert);
    }
}
