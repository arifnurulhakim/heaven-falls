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
        Schema::create('hr_inventory_players', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('weapon_primary_r_id')->nullable();
            $table->bigInteger('weapon_secondary_r_id')->nullable();
            $table->bigInteger('weapon_melee_r_id')->nullable();
            $table->bigInteger('weapon_explosive_r_id')->nullable();
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
        Schema::dropIfExists('hr_inventory_players');
    }
};
