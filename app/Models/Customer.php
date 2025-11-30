<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $primaryKey = 'customer_id';
    public $timestamps = false; // Tắt timestamp nếu bảng customers không có created_at/updated_at chuẩn
    protected $fillable = ['user_id', 'created_at'];
}