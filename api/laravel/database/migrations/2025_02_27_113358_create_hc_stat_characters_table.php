<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('hc_stat_characters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->integer('level');
            $table->integer('hitpoints');
            $table->integer('damage');
            $table->integer('defense');
            $table->integer('speed');
            $table->string('skills', 255)->nullable();
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();


            $table->foreign('character_id')->references('id')->on('hc_characters')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hc_stat_characters');
    }
};
