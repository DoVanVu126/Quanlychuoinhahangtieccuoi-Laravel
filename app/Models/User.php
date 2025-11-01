<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false; // vì bạn dùng created_at riêng

    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'image_url',
        'role',
        'created_at',
    ];
}
