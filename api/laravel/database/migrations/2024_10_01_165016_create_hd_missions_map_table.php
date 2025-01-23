<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHdMissionsMapTable extends Migration
{
    public function up()
    {
        Schema::create('hd_missions_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maps_id');
            $table->string('missions_name', 255)->nullable();
            $table->string('objective_missions', 255)->nullable();
            $table->enum('type_missions', ['TYPE_A', 'TYPE_B', 'TYPE_C']); // Modify enum values
            $table->integer('target_missions');
            $table->integer('reward_currency');
            $table->integer('reward_exp');
            $table->enum('status_missions', ['ACTIVE', 'INACTIVE']); // Modify enum values
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();

            $table->foreign('maps_id')->references('id')->on('hc_maps')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hd_missions_map');
    }
}
