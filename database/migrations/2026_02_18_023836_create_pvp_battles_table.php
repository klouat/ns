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
        Schema::create('pvp_battles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('host_id');
            $table->unsignedBigInteger('enemy_id');
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->json('log')->nullable();
            $table->string('type')->default('casual');
            $table->timestamps();

            // Foreign keys
            $table->foreign('host_id')->references('id')->on('characters')->onDelete('cascade');
            $table->foreign('enemy_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pvp_battles');
    }
};
