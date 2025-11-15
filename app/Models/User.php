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

    // Để Laravel biết dùng password_hash làm mật khẩu
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
