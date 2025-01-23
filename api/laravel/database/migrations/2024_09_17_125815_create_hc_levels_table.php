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
        Schema::create('hc_levels', function (Blueprint $table) {
        $table->id();
        $table->string('name', 255);
        $table->string('desc', 255);
        $table->string('hud', 255);
        $table->float('level_reach');
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
        Schema::dropIfExists('hc_levels');
    }
};
