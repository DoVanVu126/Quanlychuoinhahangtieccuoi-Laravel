<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $primaryKey = 'customer_id'; // nếu key của bạn là customer_id
    public $timestamps = true; // nếu muốn created_at tự động
    protected $fillable = ['user_id'];
}
