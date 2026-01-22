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
        Schema::table('characters', function (Blueprint $table) {
            $table->integer('character_ss')->default(0);
            $table->text('equipped_senjutsu_skills')->nullable();
        });

        Schema::create('character_senjutsu_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('skill_id');
            $table->integer('level')->default(0);
            $table->string('type'); // 'toad', 'snake', 'other'
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_senjutsu_skills');

        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('character_ss');
            $table->dropColumn('equipped_senjutsu_skills');
        });
    }
};
