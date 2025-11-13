<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    use HasFactory;
    
    // Tên bảng của bạn
    protected $table = 'password_reset_otps'; 

    // Tắt primary key mặc định nếu bạn không dùng 'id'
    // public $incrementing = false; 

    // Tắt timestamps (created_at, updated_at) nếu bảng của bạn không có
    public $timestamps = false; 

    protected $fillable = [
        'email',
        'otp_code',
        'expires_at'
    ];
}