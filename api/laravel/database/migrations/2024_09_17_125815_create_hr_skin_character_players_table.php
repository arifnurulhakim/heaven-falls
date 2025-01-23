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
        Schema::create('hr_skin_character_players', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('players_id')->nullable();
            $table->bigInteger('character_id')->nullable();
            $table->bigInteger('skin_id')->nullable();
            $table->tinyInteger('skin_equipped');
            $table->tinyInteger('skin_status')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_skin_character_players');
    }
};
