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

<<<<<<< HEAD
=======
    // ✅ Ẩn password_hash khi trả về JSON
>>>>>>> hai/merge
    protected $hidden = [
        'password_hash',
    ];

<<<<<<< HEAD
    // Để Laravel biết dùng password_hash làm mật khẩu
=======
    // ✅ Cast types
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * ✅ Ghi đè để Laravel biết tên cột mật khẩu
     * Cần cho authentication
     */
>>>>>>> hai/merge
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
<<<<<<< HEAD
     * Quan hệ với Customer
     * 1 User có thể có 1 Customer record (khi đặt tiệc)
=======
     * ✅ Relationship với Customer (nếu có)
>>>>>>> hai/merge
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'user_id');
    }

    /**
<<<<<<< HEAD
     * Kiểm tra user có phải là khách hàng đã đặt tiệc không
     */
    public function isCustomer()
    {
        return $this->customer()->exists();
    }

    /**
     * Scope lấy chỉ customer
     */
    public function scopeCustomersOnly($query)
    {
        return $query->where('role', 'customer');
    }

    /**
     * Scope lấy chỉ staff
     */
    public function scopeStaffOnly($query)
    {
        return $query->where('role', 'staff');
    }

    /**
     * Scope lấy chỉ admin
     */
    public function scopeAdminOnly($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope tìm kiếm user
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
=======
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
>>>>>>> hai/merge
    }
}