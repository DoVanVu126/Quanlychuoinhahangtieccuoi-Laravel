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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id('promotion_id'); // INT AUTO_INCREMENT
            $table->unsignedBigInteger('restaurant_id');
            $table->string('promotion_code', 50)->unique();
            $table->string('title', 100);
            $table->string('description', 255)->nullable();
            $table->enum('discount_type', ['percent', 'amount']);
            $table->decimal('discount_value', 18, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'expired', 'upcoming'])->default('active');
            $table->timestamp('created_at')->useCurrent();

            // Foreign key
            $table->foreign('restaurant_id')->references('restaurant_id')->on('restaurants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
