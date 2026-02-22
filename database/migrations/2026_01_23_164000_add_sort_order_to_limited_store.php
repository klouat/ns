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
        Schema::table('limited_store_items', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('group_id');
        });

        // Seed Sort Orders
        // Order 1 = Base, Order 2 = Upgrade

        // Lightning: skill_35 -> skill_87
        DB::table('limited_store_items')->where('item_id', 'skill_35')->update(['sort_order' => 1]);
        DB::table('limited_store_items')->where('item_id', 'skill_87')->update(['sort_order' => 2]);

        // Fire: skill_36 -> skill_86
        DB::table('limited_store_items')->where('item_id', 'skill_36')->update(['sort_order' => 1]);
        DB::table('limited_store_items')->where('item_id', 'skill_86')->update(['sort_order' => 2]);

        // Water: skill_60 -> skill_89
        DB::table('limited_store_items')->where('item_id', 'skill_60')->update(['sort_order' => 1]);
        DB::table('limited_store_items')->where('item_id', 'skill_89')->update(['sort_order' => 2]);

        // Earth: skill_59 -> skill_88
        DB::table('limited_store_items')->where('item_id', 'skill_59')->update(['sort_order' => 1]);
        DB::table('limited_store_items')->where('item_id', 'skill_88')->update(['sort_order' => 2]);

        // Wind: skill_39 -> skill_85
        DB::table('limited_store_items')->where('item_id', 'skill_39')->update(['sort_order' => 1]);
        DB::table('limited_store_items')->where('item_id', 'skill_85')->update(['sort_order' => 2]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('limited_store_items', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
