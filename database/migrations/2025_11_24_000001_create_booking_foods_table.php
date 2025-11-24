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
        Schema::create('booking_foods', function (Blueprint $table) {
            $table->id('booking_food_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('food_id');

            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('food_id')->references('food_id')->on('foods')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_foods');
    }
};
