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
        Schema::create('hd_players', function (Blueprint $table) {
            $table->id();
            $table->string('username', 255);
            $table->string('email', 255);
            $table->integer('gender')->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('password', 255);
            $table->bigInteger('level_r_id')->nullable();
            $table->bigInteger('inventory_r_id')->nullable();
            $table->string('players_ip_address', 15);
            $table->string('players_mac_address', 15);
            $table->tinyInteger('players_os_type')->nullable();
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
        Schema::dropIfExists('hd_players');
    }
};
