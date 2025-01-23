<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHcMapsTable extends Migration
{
    public function up()
    {
        Schema::create('hc_maps', function (Blueprint $table) {
            $table->id();
            $table->string('maps_name', 255);
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hc_maps');
    }
}
