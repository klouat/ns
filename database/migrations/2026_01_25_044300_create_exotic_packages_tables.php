<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Exotic packages table
        Schema::create('exotic_packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_id')->unique(); // e.g., 'spiritoforient', 'necromancer'
            $table->string('name'); // Display name
            $table->integer('price_tokens'); // Price in tokens
            $table->json('items'); // Array of item IDs
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Character purchases tracking
        Schema::create('character_exotic_purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('package_id');
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->unique(['character_id', 'package_id']); // Can only buy once
        });

        // Seed the packages with REAL IDs from library.json and listskill.json
        DB::table('exotic_packages')->insert([
            [
                'package_id' => 'spiritoforient',
                'name' => 'Spirit of Orient Set',
                'price_tokens' => 500,
                // Set 2389, Wpn 2385 (Orient Machete), Back 2371 (Orient Scabbard), Skill 2302 (Orient Blast)
                'items' => json_encode(['set_2389_0', 'set_2389_1', 'wpn_2385', 'back_2371', 'skill_2302']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'necromancer',
                'name' => 'Necromancer Set',
                'price_tokens' => 500,
                // Set 2378, Wpn 2378 (Dusk Knight Scythe), Back 2369 (Grim Reliquary), Skill 2300 (Dark Necrofear)
                'items' => json_encode(['set_2378_0', 'set_2378_1', 'wpn_2378', 'back_2369', 'skill_2300']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'tearsofkingdom',
                'name' => 'Tears of Kingdom Set',
                'price_tokens' => 500,
                // Set 2377, Back 2368 (Spirit of Wind Tear), Skill 2299 (Wind Tear Strike), Wpn 2390 (Nocturne's Bane - guess)
                'items' => json_encode(['set_2377_0', 'set_2377_1', 'back_2368', 'skill_2299', 'wpn_2390']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'ancientruins',
                'name' => 'Ancient Ruins Set',
                'price_tokens' => 500,
                // Set 2376 (Ruin Snatcher), Back 2367 (Robe of Unruling Verdict), Skill 2298 (Ancient Tomahawk), Wpn 2386 (Solarblade - guess)
                'items' => json_encode(['set_2376_0', 'set_2376_1', 'back_2367', 'skill_2298', 'wpn_2386']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'mechbuser',
                'name' => 'Mechbuser Set',
                'price_tokens' => 500,
                // Set 2388, Wpn 2384 (Mechbuser Sword), Back 2370 (Mechbuser Wings), Skill 2301 (Mechbuser Blast)
                'items' => json_encode(['set_2388_0', 'set_2388_1', 'wpn_2384', 'back_2370', 'skill_2301']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'firebeast',
                'name' => 'Fire Beast Set',
                'price_tokens' => 500,
                // Set 2222, Wpn 2242 (Firebeast Katana), Back 2227 (Firebeast Halo), Skill 2191 (Firebeast Magatama)
                'items' => json_encode(['set_2222_0', 'set_2222_1', 'wpn_2242', 'back_2227', 'skill_2191']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'nightingale',
                'name' => 'Nightingale Set',
                'price_tokens' => 500,
                // Set 2220, Back 2225 (Cloak), Wpn 2243 (Night Sky Katana - matches night), Skill 2189 (Aimless Throw - placeholder)
                'items' => json_encode(['set_2220_0', 'set_2220_1', 'back_2225', 'wpn_2243', 'skill_2189']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'monolith',
                'name' => 'Monolith Set',
                'price_tokens' => 500,
                // Set 2221, Wpn 2241 (Monolith Greatsword), Back 2226 (Eyes of Monolith), Skill 2190
                'items' => json_encode(['set_2221_0', 'set_2221_1', 'wpn_2241', 'back_2226', 'skill_2190']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'package_id' => 'hanyaoni',
                'name' => 'Hanyaoni Set',
                'price_tokens' => 500,
                // Set 2234, Skill 2199, Wpn 2240 (Spear of Red Mist - matches Oni/Red), Back 2228 (Purple Umbrella - Oni theme)
                'items' => json_encode(['set_2234_0', 'set_2234_1', 'wpn_2240', 'back_2228', 'skill_2199']),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('character_exotic_purchases');
        Schema::dropIfExists('exotic_packages');
    }
};
