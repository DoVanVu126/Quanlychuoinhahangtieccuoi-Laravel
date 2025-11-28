<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;
use App\Models\Notification;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'level', 'booking_count'
    ];

    // Quan hệ với user
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Hàm cập nhật level + tạo thông báo nếu tăng cấp
    public static function updateMembership($user_id)
    {
        // Đếm booking đã confirmed hoặc completed
        $booking_count = Booking::where('created_by_user_id', $user_id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        // Xác định level mới
        $newLevel = 'Normal';
        if ($booking_count >= 20) $newLevel = 'Diamond';
        elseif ($booking_count >= 15) $newLevel = 'VIP';
        elseif ($booking_count >= 10) $newLevel = 'Gold';
        elseif ($booking_count >= 5) $newLevel = 'Silver';

        // Lấy membership hiện tại
        $membership = Membership::firstOrCreate(
            ['user_id' => $user_id],
            ['booking_count' => 0, 'level' => 'Normal']
        );

        $oldLevel = $membership->level;

        // Cập nhật membership
        $membership->update([
            'booking_count' => $booking_count,
            'level' => $newLevel
        ]);

        // Nếu level thay đổi ⇒ tạo thông báo
        if ($oldLevel !== $newLevel) {
            Notification::create([
                'user_id' => $user_id,
                'title' => "Chúc mừng thăng hạng!",
                'message' => "Tài khoản của bạn đã được nâng hạng từ $oldLevel lên $newLevel.",
                'type' => 'membership',
                'is_read' => false,
            ]);
        }

        return $membership;
    }
}
