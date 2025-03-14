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
        Schema::create('hr_currencies_shops', function (Blueprint $table) {
            $table->id();
        $table->bigInteger('currency_id')->nullable();
        $table->string('name', 255)->nullable();
        $table->string('description', 255)->nullable();
        $table->integer('value')->nullable();
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
        Schema::dropIfExists('hr_currencies_shops');
    }
};
