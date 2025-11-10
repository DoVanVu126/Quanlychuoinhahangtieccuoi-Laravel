<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // Tên cột timestamp duy nhất
    const CREATED_AT = 'created_at';
    // Báo cho Laravel biết không có cột updated_at
    const UPDATED_AT = null; 

    // Chỉ định khóa chính của bạn
    protected $primaryKey = 'user_id';

    // Các trường được phép gán hàng loạt (quan trọng cho hàm create)
    protected $fillable = [
        'username',
        'email',
        'password_hash', // Phải khớp với tên cột
        'phone',
        'role',
        'full_name',
        'address',
        'image_url',
    ];

    // Ẩn cột mật khẩu khi trả về JSON
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Ghi đè hàm này để Laravel biết tên cột mật khẩu
     * (Cần cho các chức năng đăng nhập sau này)
     */
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }
}