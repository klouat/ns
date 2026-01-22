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
        Schema::create('mails', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('character_id');
            $blueprint->string('sender_name')->nullable();
            $blueprint->string('title');
            $blueprint->text('body');
            $blueprint->integer('type')->default(1); // 1: normal, 2: friend request, 3: clan invitation, etc.
            $blueprint->string('rewards')->nullable(); // comma-separated item_id:quantity
            $blueprint->boolean('is_viewed')->default(false);
            $blueprint->boolean('is_claimed')->default(false);
            $blueprint->timestamps();

            $blueprint->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mails');
    }
};
