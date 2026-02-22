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
            $table->string('group_id')->nullable()->after('category');
        });

        // Seed the pair requested: skill_35 and skill_60
        // We'll group them as 'group_lightning_water'
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_35', 'skill_60'])
            ->update(['group_id' => 'pair_35_60']);
            
        // Let's create another pair for variety: skill_36 (Fire Power) and skill_86 (Hell Fire)
        DB::table('limited_store_items')
            ->whereIn('item_id', ['skill_36', 'skill_86'])
            ->update(['group_id' => 'pair_fire']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('limited_store_items', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });
    }
};
