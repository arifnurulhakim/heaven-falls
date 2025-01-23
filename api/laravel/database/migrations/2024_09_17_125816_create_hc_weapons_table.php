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
        Schema::create('hc_weapons', function (Blueprint $table) {
            $table->id();
            $table->integer('is_active')->nullable();
            $table->tinyInteger('is_in_shop')->nullable();
            $table->bigInteger('weapon_r_type')->nullable();
            $table->string('name_weapons', 255);
            $table->text('description');
            $table->text('image')->nullable();
            $table->double('attack');
            $table->double('durability');
            $table->float('point_price')->nullable();
            $table->float('repair_price')->nullable();
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
        Schema::dropIfExists('hc_weapons');
    }
};
