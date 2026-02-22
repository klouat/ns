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
        Schema::create('limited_store_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->unique(); // e.g., skill_88
            $table->integer('price_token');
            $table->integer('price_emblem')->nullable(); // Discounted price for emblem users?
            $table->string('category')->default('skill');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('character_limited_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            // Store selected items as JSON array of item_ids
            $table->json('items')->nullable(); 
            // Timestamp for when the current store refreshes/expires
            $table->timestamp('end_time')->nullable();
            $table->integer('refresh_count')->default(0);
            $table->integer('discount')->default(0); // Store discount percentage
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
        
        // Seed some initial data
        $skills = [
            ['skill_35', 200, 150], // Kinjutsu: Lightning Charge
            ['skill_36', 200, 150], // Kinjutsu: Fire Power
            ['skill_39', 200, 150], // Kinjutsu: Evasion
            ['skill_59', 200, 150], // Kinjutsu: Golem Protection
            ['skill_60', 200, 150], // Kinjutsu: Water Renewal
            ['skill_85', 300, 240], // Kinjutsu: Blade of Wind
            ['skill_86', 300, 240], // Kinjutsu: Hell Fire
            ['skill_87', 300, 240], // Kinjutsu: Lightning Flash
            ['skill_88', 300, 240], // Kinjutsu: Earth Absorption
            ['skill_89', 300, 240], // Kinjutsu: Water Bundle
        ];

        foreach ($skills as $skill) {
            \Illuminate\Support\Facades\DB::table('limited_store_items')->insert([
                'item_id' => $skill[0],
                'price_token' => $skill[1],
                'price_emblem' => $skill[2],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_limited_stores');
        Schema::dropIfExists('limited_store_items');
    }
};
