<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = 'services';              // Tên bảng
    protected $primaryKey = 'service_id';       // Khóa chính thật sự
    public $incrementing = true;                // Nếu AUTO_INCREMENT
    public $timestamps = false;                 // Nếu không dùng created_at / updated_at

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'status',
        'image_url',
        'created_at',
    ];

    // Liên kết với Restaurant
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }
}
