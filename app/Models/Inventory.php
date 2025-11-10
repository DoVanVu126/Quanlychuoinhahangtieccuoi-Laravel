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
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reorder_level' => 'integer',
        'expiry_date' => 'date',
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

    /**
     * Kiểm tra sản phẩm đã hết hạn chưa
     */
    public function isExpired()
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isPast();
    }

    /**
     * Kiểm tra sản phẩm sắp hết hạn (còn 7 ngày)
     */
    public function isNearExpiry()
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isFuture() && 
               $this->expiry_date->diffInDays(now()) <= 7;
    }

    /**
     * Scope để lấy sản phẩm đã hết hạn
     */
    public function scopeExpired($query)
    {
        return $query->whereDate('expiry_date', '<', now());
    }

    /**
     * Scope để lấy sản phẩm sắp hết hạn
     */
    public function scopeNearExpiry($query)
    {
        return $query->whereDate('expiry_date', '>=', now())
                     ->whereDate('expiry_date', '<=', now()->addDays(7));
    }
}
