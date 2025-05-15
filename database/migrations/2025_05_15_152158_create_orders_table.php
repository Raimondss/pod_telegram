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
        Schema::create('telegram_user_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained();
            $table->foreignId('telegram_user_variant_id')->constrained();

            // Telegram payment fields
            $table->string('currency', 10);
            $table->unsignedInteger('total_amount'); // Stored in smallest currency unit (e.g. cents)

            // Order info
            $table->string('email')->nullable();
            $table->string('name')->nullable();

            // Shipping address
            $table->string('country_code', 5)->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('street_line1')->nullable();
            $table->string('street_line2')->nullable();
            $table->string('post_code')->nullable();

            $table->timestamps(); // created_at, updated_at

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
