<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hc_topup', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_in_shop')->default(false);

            $table->string('name_topup');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('amount');

            $table->unsignedBigInteger('currency_id'); // Currency yang akan diterima
            $table->foreign('currency_id')->references('id')->on('hc_currencies')->onDelete('cascade');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hc_topup');
    }
};
