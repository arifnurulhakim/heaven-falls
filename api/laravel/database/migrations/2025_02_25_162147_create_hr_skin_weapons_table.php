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
        Schema::create('hr_skin_weapons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('weapon_id');
            $table->integer('level');
            $table->string('name_skin', 255);
            $table->string('code_skin', 255);
            $table->string('image_skin', 255);
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();
            $table->foreign('weapon_id')->references('id')->on('hc_weapons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_skin_weapons');
    }
};
