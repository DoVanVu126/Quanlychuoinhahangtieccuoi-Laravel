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
        // Tạo bảng mới để lưu mã OTP
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->string('email')->primary(); // Dùng email làm khóa chính
            $table->string('otp_code');       // Cột để lưu 6 số OTP
            $table->timestamp('expires_at');  // Cột lưu thời gian hết hạn
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
