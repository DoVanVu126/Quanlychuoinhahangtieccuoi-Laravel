<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    use HasFactory;

    protected $table = 'halls';
    protected $primaryKey = 'hall_id'; // ✅ nếu bảng dùng hall_id

    protected $fillable = [
        'name',
        'restaurant_id',
        'capacity',
        'price',
        'description',
        'status',
        'image_url',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}
