<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';
    protected $primaryKey = 'inventory_id';

    protected $fillable = [
        'restaurant_id',
        'item_name',
        'unit',
        'quantity',
        'reorder_level',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    /**
     * Relationship với Restaurant
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id', 'restaurant_id');
    }

    /**
     * Kiểm tra xem sản phẩm có cần đặt hàng lại không
     */
    public function needsReorder()
    {
        return $this->quantity <= $this->reorder_level;
    }

    /**
     * Scope để lấy các sản phẩm cần đặt hàng lại
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    /**
     * Scope để lọc theo nhà hàng
     */
    public function scopeByRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }
}
