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
        Schema::create('hd_wallets', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('player_id');
            $table->bigInteger('currency_id');
            $table->decimal('amount', 10, 2);
            $table->string('category', 255)->nullable();
            $table->string('label', 255)->nullable();
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
        Schema::dropIfExists('hd_wallets');
    }
};
