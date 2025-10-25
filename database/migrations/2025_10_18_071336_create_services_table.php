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
        Schema::create('services', function (Blueprint $table) {
            $table->id('service_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->decimal('price', 18, 2);
            $table->enum('status', ['available', 'unavailable', 'maintenance'])
                  ->default('available');
            $table->string('image_url', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Khóa ngoại
            $table->foreign('restaurant_id')
                  ->references('restaurant_id')
                  ->on('restaurants')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
