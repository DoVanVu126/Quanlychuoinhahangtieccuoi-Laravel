<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Tên cột timestamp duy nhất
    const CREATED_AT = 'created_at';
    // Không có cột updated_at
    const UPDATED_AT = null;

    // Chỉ định khóa chính
    protected $primaryKey = 'customer_id';

    // Các trường được phép gán hàng loạt
    protected $fillable = [
        'user_id',
    ];
}