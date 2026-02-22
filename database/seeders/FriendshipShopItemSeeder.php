<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FriendshipShopItem;

class FriendshipShopItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['id' => 1,  'price' => 2,  'item' => 'essential_01'],
            ['id' => 2,  'price' => 2,  'item' => 'essential_02'],
            ['id' => 3,  'price' => 10, 'item' => 'gold_5000'],
            ['id' => 4,  'price' => 5,  'item' => 'xp_5000'],
            ['id' => 5,  'price' => 3,  'item' => 'essential_03'],
            ['id' => 6,  'price' => 3,  'item' => 'essential_04'],
            ['id' => 7,  'price' => 8,  'item' => 'gold_10000'],
            ['id' => 8,  'price' => 6,  'item' => 'xp_10000'],
            ['id' => 9,  'price' => 12, 'item' => 'token_1000'],
            ['id' => 10, 'price' => 15, 'item' => 'skill_2112'],
        ];

        foreach ($items as $item) {
            FriendshipShopItem::updateOrCreate(
                ['id' => $item['id']],
                [
                    'price' => $item['price'],
                    'item'  => $item['item'],
                ]
            );
        }
    }
}
