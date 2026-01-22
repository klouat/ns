<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->unsignedBigInteger('friend_id');
            $table->tinyInteger('status')->default(0); // 0: pending, 1: accepted
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->unique(['character_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};
