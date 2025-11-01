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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id('booking_id');

            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('hall_id');

            $table->string('event_type', 50)->nullable();
            $table->time('event_time');
            $table->date('event_date');
            $table->date('return_date')->nullable();
            $table->integer('number_of_tables');
            $table->string('status', 20)->default('pending');
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // ✅ Ràng buộc khóa ngoại
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('restaurant_id')->on('restaurants')->onDelete('cascade');
            $table->foreign('hall_id')->references('hall_id')->on('halls')->onDelete('cascade');

            // ✅ Ràng buộc unique
            $table->unique(['hall_id', 'event_date', 'event_time'], 'uq_hall_booking_time');
        });

        // ✅ Thêm CHECK constraint thủ công
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT chk_tables_positive CHECK (number_of_tables > 0)');
        DB::statement('ALTER TABLE bookings ADD CONSTRAINT chk_return_date CHECK (return_date IS NULL OR return_date >= event_date)');
        DB::statement("ALTER TABLE bookings ADD CONSTRAINT chk_status CHECK (status IN ('pending','confirmed','cancelled','completed'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
