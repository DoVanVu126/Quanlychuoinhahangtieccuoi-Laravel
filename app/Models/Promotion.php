<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';
    protected $primaryKey = 'promotion_id';
    public $timestamps = false;

    protected $fillable = [
        'restaurant_id',
        'promotion_code',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'status',
        'image', // <-- thêm trường ảnh
        'created_at',
    ];

    // Liên kết với Restaurant
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }

}
