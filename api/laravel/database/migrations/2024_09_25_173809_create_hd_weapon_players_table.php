<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHdWeaponPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hd_weapon_players', function (Blueprint $table) {
            $table->id();
           // Ensure inventory_id and weapon_id are unsigned big integers
           $table->foreignId('inventory_id')->constrained('hf_hr_inventory_players')->onDelete('cascade');
           $table->foreignId('weapon_id')->constrained('hf_hc_currencies')->onDelete('cascade');

           // Ensure created_by and modified_by are unsigned big integers
           $table->unsignedBigInteger('created_by');
           $table->unsignedBigInteger('modified_by')->nullable();

           // Add foreign key constraints
           $table->foreign('created_by')->references('id')->on('hf_users')->onDelete('cascade');
           $table->foreign('modified_by')->references('id')->on('hf_users')->onDelete('set null');

           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hd_waapon_players');
    }
}
