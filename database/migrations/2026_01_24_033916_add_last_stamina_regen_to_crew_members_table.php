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
        Schema::table('crew_members', function (Blueprint $table) {
            $table->timestamp('last_stamina_regen')->nullable()->after('stamina');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crew_members', function (Blueprint $table) {
            $table->dropColumn('last_stamina_regen');
        });
    }
};
