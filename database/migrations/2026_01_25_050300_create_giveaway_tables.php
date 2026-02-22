<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        // Main giveaway table
        Schema::create('giveaways', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->json('prizes'); // Array of item IDs ["item_1:1", "gold_1000"]
            $table->json('requirements'); // Array of reqs [{"name": "Level 10", "total": 10, "type": "level"}]
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->boolean('processed')->default(false); // If winners have been picked
            $table->timestamps();
        });

        // Participation tracking
        Schema::create('character_giveaways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('giveaway_id');
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('giveaway_id')->references('id')->on('giveaways')->onDelete('cascade');
            $table->unique(['character_id', 'giveaway_id']);
        });

        // Winners table
        Schema::create('giveaway_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('giveaway_id');
            $table->unsignedBigInteger('character_id');
            $table->string('character_name'); // Capture name at time of win
            $table->json('prize_won');
            $table->timestamp('won_at');
            $table->boolean('claimed')->default(false);
            $table->timestamps();

            $table->foreign('giveaway_id')->references('id')->on('giveaways')->onDelete('cascade');
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });

        // Seed some data
        $now = Carbon::now();
        
        // Active Giveaway
        DB::table('giveaways')->insert([
            'title' => 'Weekly Ninja Scroll Giveaway',
            'description' => 'Join now to win an exclusive Ninja Scroll! 5 winners will be selected.',
            'prizes' => json_encode(['material_12:5', 'gold_100000']),
            'requirements' => json_encode([
                ['name' => 'Minimum Level 10', 'total' => 10, 'type' => 'level'],
                ['name' => 'Join Fee: 1000 Gold', 'total' => 1000, 'type' => 'gold_fee']
            ]),
            'start_at' => $now->copy()->subDays(1),
            'end_at' => $now->copy()->addDays(2),
            'processed' => false,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // Past Giveaway (Processed)
        $pastId = DB::table('giveaways')->insertGetId([
            'title' => 'Grand Opening Celebration',
            'description' => 'Celebration event giveaway! Win 500 Tokens!',
            'prizes' => json_encode(['tokens_500']),
            'requirements' => json_encode([
                ['name' => 'Any Level', 'total' => 1, 'type' => 'level']
            ]),
            'start_at' => $now->copy()->subDays(10),
            'end_at' => $now->copy()->subDays(3),
            'processed' => true,
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('giveaway_winners');
        Schema::dropIfExists('character_giveaways');
        Schema::dropIfExists('giveaways');
    }
};
