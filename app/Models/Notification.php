<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'promotion_id',
        'membership_id',
        'support_ticket_id', // <--- THÊM DÒNG NÀY
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $attributes = [
        'is_read' => false,
        'type' => 'info',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id', 'promotion_id');
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class, 'membership_id', 'membership_id');
    }

    // --- THÊM QUAN HỆ MỚI ---
    public function supportTicket()
    {
        // Lưu ý: ticket_id là khóa chính của bảng support_tickets
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id', 'ticket_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}