<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $primaryKey = 'restaurant_id';

    /**
     * Thêm các thuộc tính ảo được append khi model được chuyển thành mảng/JSON.
     * - halls_count: số sảnh thuộc nhà hàng
     * - has_promotion: có khuyến mãi đang hoạt động hay không
     */
    protected $appends = [
        'halls_count',
        'has_promotion',
    ];

    protected $fillable = [
        'name',
        'description',
        'ward',
        'city',
        'phone',
        'email',
        'capacity',
        'price_table',
        'star_rating',
        'review_count',
        'image_url',
    ];
    
    /**
     * Quan hệ: một nhà hàng có nhiều sảnh
     */
    public function halls()
    {
        return $this->hasMany(Hall::class, 'restaurant_id', 'restaurant_id');
    }

    /**
     * Quan hệ: một nhà hàng có nhiều khuyến mãi
     */
    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'restaurant_id', 'restaurant_id');
    }

    /**
     * Attribute: số lượng sảnh (halls_count)
     */
    public function getHallsCountAttribute()
    {
        // Lưu ý: gọi count() sẽ tạo query riêng cho mỗi model nếu không eager-load
        return $this->halls()->count();
    }

    /**
     * Attribute: có khuyến mãi đang hoạt động hay không (has_promotion)
     */
    public function getHasPromotionAttribute()
    {
        $now = now();
        return $this->promotions()
            ->where('status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->exists();
    }
}
