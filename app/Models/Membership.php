<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'level', 'booking_count'
    ];

    // Quan hệ với user
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Cập nhật membership dựa trên số booking của user
   public static function updateMembership($user_id) {
    // Tính tất cả booking đã xác nhận hoặc hoàn tất
    $booking_count = Booking::where('created_by_user_id', $user_id)
                            ->whereIn('status', ['confirmed','completed'])
                            ->count();

    $level = 'Normal';
    if ($booking_count >= 20) $level = 'Diamond';
    elseif ($booking_count >= 15) $level = 'VIP';
    elseif ($booking_count >= 10) $level = 'Gold';
    elseif ($booking_count >= 5) $level = 'Silver';
    else $level = 'Normal';

    return Membership::updateOrCreate(
        ['user_id' => $user_id],
        ['booking_count' => $booking_count, 'level' => $level]
    );
}
}
