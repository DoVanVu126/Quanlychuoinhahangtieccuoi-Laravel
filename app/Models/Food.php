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
        'price',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function foodType()
    {
        return $this->belongsTo(FoodType::class, 'food_type_id');
    }
    public function suggestionPackages()
    {
        return $this->belongsToMany(
            \App\Models\SuggestionPackage::class,
            'suggestion_foods',
            'food_id',
            'package_id'
        )->withPivot('suggestion_food_id')->withTimestamps();
    }
}
