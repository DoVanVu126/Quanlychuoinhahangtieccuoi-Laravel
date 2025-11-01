<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $primaryKey = 'service_id'; // vì khóa chính của bạn là service_id
    public $timestamps = false; // nếu bạn chỉ dùng created_at

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'price',
        'status',
        'image_url',
        'created_at',
    ];

    // Liên kết với Restaurant (nếu muốn)
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }
}
