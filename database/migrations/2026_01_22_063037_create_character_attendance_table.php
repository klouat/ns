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
        Schema::create('character_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->json('attendance_days')->nullable(); 
            $table->json('claimed_milestones')->nullable(); 
            $table->date('last_token_claim')->nullable();
            $table->date('last_xp_claim')->nullable();
            $table->date('last_scroll_claim')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_attendance');
    }
};
