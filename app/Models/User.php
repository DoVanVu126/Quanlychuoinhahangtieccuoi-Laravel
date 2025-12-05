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

    protected $hidden = [
        'password_hash',
    ];

    // Để Laravel biết dùng password_hash làm mật khẩu
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Quan hệ với Customer
     * 1 User có thể có 1 Customer record (khi đặt tiệc)
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'user_id');
    }

    /**
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
    }
}