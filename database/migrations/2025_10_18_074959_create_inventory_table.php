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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('inventory_id');            // Khóa chính
            $table->unsignedBigInteger('restaurant_id'); // Khóa ngoại tới Restaurants
            $table->string('item_name', 100);
            $table->string('unit', 50);
            $table->decimal('quantity', 18, 2)->default(0);
            $table->decimal('reorder_level', 18, 2)->default(0);
            $table->timestamps(); // tự tạo created_at và updated_at

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
        Schema::dropIfExists('inventory');
    }
};
