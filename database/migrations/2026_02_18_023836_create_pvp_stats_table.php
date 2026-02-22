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
        Schema::create('pvp_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id')->unique();
            $table->integer('rank')->default(0); // 0: Rookie, etc.
            $table->integer('trophies')->default(0);
            $table->integer('points')->default(0); // PvP Currency
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('flee')->default(0);
            $table->integer('streak')->default(0);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pvp_stats');
    }
};
