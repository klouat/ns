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
        Schema::create('crews', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('master_id');
            $table->unsignedBigInteger('elder_id')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedBigInteger('golds')->default(0);
            $table->unsignedBigInteger('tokens')->default(0);
            $table->unsignedInteger('kushi_dango')->default(1);
            $table->unsignedInteger('tea_house')->default(1);
            $table->unsignedInteger('bath_house')->default(1);
            $table->unsignedInteger('training_centre')->default(1);
            $table->unsignedInteger('max_members')->default(20);
            $table->text('announcement')->nullable();
            $table->timestamp('last_renamed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('crew_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crew_id');
            $table->unsignedBigInteger('char_id');
            $table->unsignedTinyInteger('role')->default(3); // 1: Master, 2: Elder, 3: Member
            $table->unsignedInteger('contribution')->default(0);
            $table->unsignedBigInteger('gold_donated')->default(0);
            $table->unsignedBigInteger('token_donated')->default(0);
            $table->unsignedInteger('stamina')->default(100);
            $table->unsignedInteger('max_stamina')->default(100);
            $table->unsignedInteger('merit')->default(0);
            $table->unsignedBigInteger('damage')->default(0);
            $table->unsignedInteger('boss_kill')->default(0);
            $table->unsignedInteger('mini_game_energy')->default(5);
            $table->timestamp('last_mini_game_energy_refill')->nullable();
            $table->timestamp('role_switch_cooldown')->nullable(); // For battle role switching
            $table->timestamps();

            $table->unique(['crew_id', 'char_id']);
            $table->unique('char_id');
        });

        Schema::create('crew_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crew_id');
            $table->unsignedBigInteger('char_id');
            $table->timestamps();

            $table->unique(['crew_id', 'char_id']);
        });

        Schema::create('castles', function (Blueprint $table) {
            $table->id(); // 1-7
            $table->string('name');
            $table->unsignedBigInteger('owner_crew_id')->default(0);
            $table->unsignedInteger('wall_hp')->default(100);
            $table->unsignedInteger('defender_hp')->default(100);
            $table->timestamps();
        });

        Schema::create('crew_seasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('season_number')->unique();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedTinyInteger('phase')->default(1); // 1 or 2
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crew_season_rankings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('crew_id');
            $table->unsignedInteger('rank');
            $table->unsignedBigInteger('damage');
            $table->unsignedInteger('merit');
            $table->unsignedBigInteger('tokens_won');
            $table->integer('members_count');
            $table->timestamps();

            $table->unique(['season_id', 'crew_id']);
        });

        Schema::create('crew_history_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crew_id');
            $table->text('message');
            $table->timestamps();
        });

        // Optional: Table for Boss configuration if not hardcoded
        Schema::create('crew_bosses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('mission_id'); // battleBackground
            $table->json('levels'); // [min, max] level adjustment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crew_bosses');
        Schema::dropIfExists('crew_history_logs');
        Schema::dropIfExists('crew_season_rankings');
        Schema::dropIfExists('crew_seasons');
        Schema::dropIfExists('castles');
        Schema::dropIfExists('crew_requests');
        Schema::dropIfExists('crew_members');
        Schema::dropIfExists('crews');
    }
};
