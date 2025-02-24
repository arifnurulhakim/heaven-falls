<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hc_topup_currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('topup_id');
            $table->unsignedBigInteger('currency_id'); // Currency yang digunakan untuk membeli
            $table->decimal('price_topup', 10, 2); // Harga berdasarkan currency

            // Foreign Keys
            $table->foreign('topup_id')->references('id')->on('hc_topup')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('hc_currencies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hc_topup_currencies');
    }
};
