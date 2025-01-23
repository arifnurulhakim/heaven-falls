<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHdMissionsRewardsTable extends Migration
{
    public function up()
    {
        Schema::create('hd_missions_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_missions_map');
            $table->unsignedBigInteger('id_player');
            $table->integer('reward_currency');
            $table->integer('reward_exp');
            $table->boolean('claim_status')->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();

            // $table->foreign('id_missions_map')->references('id')->on('hd_missions_map')->onDelete('cascade');
            // $table->foreign('id_player')->references('id')->on('players')->onDelete('cascade'); // Replace with actual player table
        });
    }

    public function down()
    {
        Schema::dropIfExists('hd_missions_rewards');
    }
}
