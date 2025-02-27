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
        Schema::create('hc_sub_type_weapons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('type_weapon_id');
            $table->string('name', 255);
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps();

            $table->foreign('type_weapon_id')->references('id')->on('hc_type_weapons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hc_sub_type_weapons');
    }
};
