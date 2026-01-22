<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('character_talent_skills', function (Blueprint $table) {
            $table->renameColumn('talent_name', 'talent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('character_talent_skills', function (Blueprint $table) {
            $table->renameColumn('talent_id', 'talent_name');
        });
    }
};
