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
        Schema::create('character_pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('pet_swf');
            $table->string('pet_name');
            $table->integer('pet_level')->default(1);
            $table->integer('pet_xp')->default(0);
            $table->integer('pet_mp')->default(100);
            $table->string('pet_skills')->default('0,0,0,0,0,0');
            $table->timestamps();
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedBigInteger('equipped_pet_id')->nullable()->after('equipment_skills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_pets');
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn('equipped_pet_id');
        });
    }
};
