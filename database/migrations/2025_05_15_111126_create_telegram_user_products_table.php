<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_user_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained();
            $table->string('status')->index();
            $table->string('uploaded_file_url');
            $table->string('design_name');
            $table->integer('product_id');
            $table->integer('category');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_user_products');
    }
};
