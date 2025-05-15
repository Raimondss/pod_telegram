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
        Schema::create('telegram_user_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_product_id');
            $table->string('status')->index();
            $table->integer('variant_id');
            $table->string('color');
            $table->string('size');
            $table->integer('price'); //ONLY USD
            $table->string('mockup_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user_variants');
    }
};
