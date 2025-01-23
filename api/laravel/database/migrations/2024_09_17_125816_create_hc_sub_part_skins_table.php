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
        Schema::create('hc_sub_part_skins', function (Blueprint $table) {
            $table->id();
        $table->bigInteger('part_id_skin')->nullable();
        $table->string('name_sub_part_skin', 255);
        $table->string('code_sub_part_skin', 255);
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
        Schema::dropIfExists('hc_sub_part_skins');
    }
};
