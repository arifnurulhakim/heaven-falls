<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrLevelPlayersLogTable extends Migration
{
    public function up()
    {
        Schema::create('hr_level_players_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('level_player_id');
            $table->integer('exp');
            $table->timestamps();

            // Add foreign key constraint if level_player_id references another table
            // $table->foreign('level_player_id')->references('id')->on('level_players')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_level_players_log');
    }
}
