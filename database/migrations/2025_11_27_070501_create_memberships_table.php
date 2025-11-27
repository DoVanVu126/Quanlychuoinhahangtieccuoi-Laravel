<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique(); // mỗi user 1 membership
            $table->string('level', 20)->default('Normal'); // Normal / Silver / Gold / VIP / Diamond
            $table->integer('booking_count')->default(0);   // số lần đặt bàn
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
