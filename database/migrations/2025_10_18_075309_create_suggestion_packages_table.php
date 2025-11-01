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
        Schema::create('suggestion_packages', function (Blueprint $table) {
            $table->id('package_id');                   // Khóa chính
            $table->unsignedBigInteger('restaurant_id'); // Khóa ngoại tới Restaurants
            $table->string('name', 100);
            $table->string('event_type', 50)->nullable();
            $table->unsignedBigInteger('hall_id')->nullable(); // Khóa ngoại tới Halls (nullable)
            $table->integer('number_of_tables')->nullable();
            $table->string('description', 255)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Khóa ngoại
            $table->foreign('restaurant_id')
                  ->references('restaurant_id')
                  ->on('restaurants')
                  ->onDelete('cascade');

            $table->foreign('hall_id')
                  ->references('hall_id')
                  ->on('halls')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suggestion_packages');
    }
};
