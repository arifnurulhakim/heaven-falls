<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('hc_stat_weapons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('weapon_id');
            $table->integer('level');
            $table->integer('accuracy');
            $table->integer('damage');
            $table->integer('range');
            $table->decimal('fire_rate', 8, 2); // Menggunakan decimal agar bisa 0.0
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();


            $table->foreign('weapon_id')->references('id')->on('hc_weapons')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hc_stat_weapons');
    }
};
