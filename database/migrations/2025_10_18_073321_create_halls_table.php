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
        Schema::create('halls', function (Blueprint $table) {
            $table->id('hall_id'); // Khóa chính
            $table->unsignedBigInteger('restaurant_id'); // Khóa ngoại liên kết nhà hàng
            $table->string('name', 100);
            $table->integer('capacity')->nullable();
            $table->decimal('price', 18, 2)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('status', 50)->default('available');
            $table->string('image_url', 255)->nullable();
            $table->timestamps();

            // Khóa ngoại tham chiếu đến bảng restaurants
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
        Schema::dropIfExists('halls');
    }
};
