<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialMarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('material_market_items')->truncate();

        DB::table('material_market_items')->insert([
            [
                'item_id' => 'wpn_7016',
                'category' => 'wpn',
                'materials' => json_encode(['material_01', 'material_02']),
                'quantities' => json_encode([1, 1]),
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 'wpn_7015',
                'category' => 'wpn',
                'materials' => json_encode(['material_01', 'material_02']),
                'quantities' => json_encode([1, 1]),
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'item_id' => 'wpn_7014',
                'category' => 'wpn',
                'materials' => json_encode(['material_01', 'material_02']),
                'quantities' => json_encode([1, 1]),
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
