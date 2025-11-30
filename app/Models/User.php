<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'image_url',
        'role',
        'created_at',
        'phone',
        'address',
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