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
            $table->string('skill_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('character_talent_skills', function (Blueprint $table) {
            $table->unsignedBigInteger('skill_id')->change();
        });
    }
};
