<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hr_stat_weapon_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('weapon_id')->constrained('hc_weapons')->onDelete('cascade');
            $table->integer('level');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_stat_weapon_players');
    }
};
