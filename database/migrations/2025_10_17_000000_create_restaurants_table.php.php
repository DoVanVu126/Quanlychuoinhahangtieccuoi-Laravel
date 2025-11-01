<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id('restaurant_id'); // Khóa chính
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('ward', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price_table', 18, 2)->nullable();
            $table->decimal('star_rating', 2, 1)->default(0);
            $table->integer('review_count')->default(0);
            $table->string('image_url', 255)->nullable();
            $table->timestamps(); // tự động tạo created_at, updated_at
        });

        // Thêm ràng buộc CHECK cho star_rating (chỉ hỗ trợ SQL Server, PostgreSQL, v.v.)
        DB::statement('ALTER TABLE restaurants ADD CONSTRAINT chk_star_rating CHECK (star_rating BETWEEN 0 AND 5)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
