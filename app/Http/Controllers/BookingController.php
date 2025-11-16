<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Lấy tất cả booking theo user_id
    public function BookingbyUser(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'message' => 'Bạn cần cung cấp user_id'
            ], 400);
        }

        // Lấy booking của user, có thể eager load hall nếu cần
        $bookings = Booking::with('hall') // 'hall' để lấy tên sảnh
            ->where('created_by_user_id', $userId)
            ->orderBy('event_date', 'desc')
            ->get();

        // Nếu muốn trả hall_name luôn
        $bookings = $bookings->map(function ($b) {
            return [
                'booking_id' => $b->booking_id,
                'customer_id' => $b->customer_id,
                'created_by_user_id' => $b->created_by_user_id,
                'restaurant_id' => $b->restaurant_id,
                'hall_id' => $b->hall_id,
                'hall_name' => $b->hall ? $b->hall->name : null,
                'event_type' => $b->event_type,
                'event_time' => $b->event_time,
                'event_date' => $b->event_date,
                'return_date' => $b->return_date,
                'number_of_tables' => $b->number_of_tables,
                'status' => $b->status,
                'notes' => $b->notes,
                'created_at' => $b->created_at,
            ];
        });

        return response()->json($bookings);
    }
}
