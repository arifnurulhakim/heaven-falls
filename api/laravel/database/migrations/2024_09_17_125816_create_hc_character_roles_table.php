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
        Schema::create('hc_character_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role', 255)->nullable();
            $table->string('hitpoints', 255)->nullable();
            $table->string('damage', 255)->nullable();
            $table->string('defense', 255)->nullable();
            $table->string('speed', 255)->nullable();
            $table->string('skills', 255)->nullable();
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
        Schema::dropIfExists('hc_character_roles');
    }
};
