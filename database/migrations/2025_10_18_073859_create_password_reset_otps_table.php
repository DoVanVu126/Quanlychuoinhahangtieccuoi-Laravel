<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
<<<<<<< HEAD
        // Tạo bảng mới để lưu mã OTP
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->string('email')->primary(); // Dùng email làm khóa chính
            $table->string('otp_code');       // Cột để lưu 6 số OTP
            $table->timestamp('expires_at');  // Cột lưu thời gian hết hạn
=======
        // Xóa bảng cũ nếu tồn tại để tránh lỗi
        Schema::dropIfExists('password_reset_otps');

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id(); 
            // SỬA 1: Bỏ cột user_id vì Controller không gửi lên (dùng email để định danh là đủ)
            // $table->unsignedBigInteger('user_id'); 
            
            $table->string('email', 255)->index(); // Thêm index cho email để tìm nhanh hơn
            
            // SỬA 2: Đổi tên 'otp' thành 'otp_code' cho KHỚP với Controller
            $table->string('otp_code'); 
            
            // Controller không dùng is_used trong logic insert, nhưng cứ để default false
            $table->boolean('is_used')->default(false);
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at');

            // Bỏ khóa ngoại user_id vì đã bỏ cột user_id
>>>>>>> hai/merge
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};