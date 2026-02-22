<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shadow War seasons
        Schema::create('shadow_war_seasons', function (Blueprint $table) {
            $table->id();
            $table->integer('num')->default(1);
            $table->string('date')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Player shadow war data per season
        Schema::create('shadow_war_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('season_id');
            $table->integer('squad')->default(0);      // 0=assault,1=ambush,2=medic,3=kage,4=hq
            $table->integer('trophy')->default(0);
            $table->integer('rank')->default(0);        // 0=bronze..7=sage
            $table->integer('energy')->default(100);
            $table->boolean('show_profile')->default(true);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('shadow_war_seasons')->onDelete('cascade');
            $table->unique(['character_id', 'season_id']);
            $table->index(['season_id', 'trophy']);
            $table->index(['season_id', 'squad', 'trophy']);
        });

        // Shadow War presets (defender loadout)
        Schema::create('shadow_war_presets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('name')->default('Preset');
            $table->string('weapon')->nullable();
            $table->string('clothing')->nullable();
            $table->string('hair')->nullable();
            $table->string('back_item')->nullable();
            $table->string('accessory')->nullable();
            $table->string('hair_color')->nullable();
            $table->string('skin_color')->nullable();
            $table->text('skills')->nullable();
            $table->string('pet_swf')->nullable();
            $table->unsignedBigInteger('pet_id')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->index('character_id');
        });

        // Cached enemy matchmaking list
        Schema::create('shadow_war_enemy_cache', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('season_id');
            $table->json('enemies');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('shadow_war_seasons')->onDelete('cascade');
            $table->unique(['character_id', 'season_id']);
        });

        // Battle logs
        Schema::create('shadow_war_battles', function (Blueprint $table) {
            $table->id();
            $table->string('battle_code')->unique();
            $table->unsignedBigInteger('attacker_id');
            $table->unsignedBigInteger('defender_id');
            $table->unsignedBigInteger('season_id');
            $table->integer('trophies_change')->default(0);
            $table->boolean('is_finished')->default(false);
            $table->timestamps();

            $table->foreign('attacker_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('defender_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('shadow_war_seasons')->onDelete('cascade');
            $table->index('battle_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadow_war_battles');
        Schema::dropIfExists('shadow_war_enemy_cache');
        Schema::dropIfExists('shadow_war_presets');
        Schema::dropIfExists('shadow_war_players');
        Schema::dropIfExists('shadow_war_seasons');
    }
};
