<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id('food_id');
            $table->foreignId('food_type_id')->constrained('food_types', 'food_type_id')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurants', 'restaurant_id')->onDelete('cascade');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('unit', 50);
            $table->string('image_url', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
