<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Lightning Pair
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_35', 'skill_87'])
            ->update(['group_id' => 'group_kinjutsu_lightning']);

        // Fire Pair
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_36', 'skill_86'])
            ->update(['group_id' => 'group_kinjutsu_fire']);

        // Water Pair
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_60', 'skill_89'])
            ->update(['group_id' => 'group_kinjutsu_water']);

        // Earth Pair
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_59', 'skill_88'])
            ->update(['group_id' => 'group_kinjutsu_earth']);

        // Wind Pair
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_39', 'skill_85'])
            ->update(['group_id' => 'group_kinjutsu_wind']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('limited_store_items')
            ->update(['group_id' => null]);
    }
};
