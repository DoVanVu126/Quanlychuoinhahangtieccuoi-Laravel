<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

     protected $table = 'foods'; // ✅ thêm nếu chưa có
    protected $primaryKey = 'food_id'; // ✅ để Eloquent hiểu khóa chính
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'food_type_id',
        'restaurant_id',
        'name',
        'description',
        'unit',
        'image_url',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function foodType()
    {
        return $this->belongsTo(FoodType::class, 'food_type_id');
    }
}
