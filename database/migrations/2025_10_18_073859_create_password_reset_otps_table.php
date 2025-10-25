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
       Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();  // Khóa chính tự tăng
            $table->unsignedBigInteger('user_id'); // Khóa ngoại liên kết users
            $table->string('email', 255);
            $table->char('otp', 6);
            $table->boolean('is_used')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');

            // Khóa ngoại
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
