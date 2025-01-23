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
        Schema::create('hd_kdas', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->unsignedBigInteger('player_id');
            $table->integer('kill');
            $table->integer('death');
            $table->integer('assist');
            $table->string('room_code', 255);
            $table->string('created_by', 255)->nullable();
            $table->string('modified_by', 255)->nullable();
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_kdas');
    }
};
