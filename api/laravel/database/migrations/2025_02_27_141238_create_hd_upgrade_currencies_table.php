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
        Schema::create('hd_upgrade_currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->enum('category', ['weapon', 'character']); // Enum hanya untuk weapon dan character
            $table->unsignedBigInteger('weapon_id')->nullable();
            $table->unsignedBigInteger('character_id')->nullable();
            $table->integer('level');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            // Relasi ke tabel currency
            $table->foreign('currency_id')->references('id')->on('hc_currencies')->onDelete('cascade');

            // Relasi ke tabel weapon (jika kategori weapon)
            $table->foreign('weapon_id')->references('id')->on('hc_weapons')->onDelete('cascade');

            // Relasi ke tabel character (jika kategori character)
            $table->foreign('character_id')->references('id')->on('hc_characters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_upgrade_currencies');
    }
};
