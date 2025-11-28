<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');            // liên kết user
            $table->unsignedBigInteger('booking_id')->nullable();     // liên kết booking
            $table->unsignedBigInteger('promotion_id')->nullable();   // liên kết promotion
            $table->string('title');                          // tiêu đề thông báo
            $table->text('message');                          // nội dung thông báo
            $table->string('type')->default('info');          // success, info, warning, error
            $table->boolean('is_read')->default(false);       // trạng thái đã đọc
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('booking_id')->nullable();
$table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('promotion_id')->references('promotion_id')->on('promotions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
