<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // Các trường có thể mass assign
    protected $fillable = [
        'user_id',
        'booking_id',
        'promotion_id',
        'membership_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    // Giá trị mặc định
    protected $attributes = [
        'is_read' => false,
        'type' => 'info', // nếu không có type thì mặc định info
    ];

    // Quan hệ với User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Quan hệ với Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    // Quan hệ với Promotion
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'promotion_id');
    }

    // Quan hệ với Membership
    public function membership()
    {
        return $this->belongsTo(Membership::class, 'membership_id', 'membership_id');
    }

    // Scope helper: chỉ lấy chưa đọc
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
