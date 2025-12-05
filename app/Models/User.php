<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ✅ Cấu hình từ project cũ
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';
    
    // ✅ Timestamps: chỉ có created_at, không có updated_at
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    public $timestamps = true; // Bật timestamps để Laravel tự động set created_at

    // ✅ Fillable: kết hợp từ cả 2 project
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'phone',
        'role',
        'full_name',
        'address',
        'image_url',
        'created_at',
    ];

    // ✅ Ẩn password_hash khi trả về JSON
    protected $hidden = [
        'password_hash',
    ];

    // ✅ Cast types
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * ✅ Ghi đè để Laravel biết tên cột mật khẩu
     * Cần cho authentication
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * ✅ Relationship với Customer (nếu có)
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'user_id');
    }

    /**
     * ✅ Relationship với Bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'user_id');
    }

    /**
     * ✅ Relationship với Notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }
}