<?php

namespace App\Http\Controllers;

use App\Models\Customer;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // Lấy danh sách bookings, có thể filter theo restaurant, customer, status
    public function index(Request $request)
    {
        $query = Booking::query();

        if ($request->has('restaurant_id') && $request->restaurant_id != '') {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        return $query->get();
    }

    // Tạo booking mới
    public function store(Request $request)
    {
        $user = $request->user(); // Lấy user hiện tại từ token/auth middleware
        if (!$user) {
            return response()->json(['message' => 'User chưa đăng nhập'], 401);
        }

        // Kiểm tra xem customer đã tồn tại chưa
        $customer = Customer::firstOrCreate(['user_id' => $user->user_id]);


        // Validate dữ liệu còn lại
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,restaurant_id',
            'hall_id' => 'required|integer|exists:halls,hall_id',
            'event_type' => 'required|string|max:255',
            'event_time' => 'required|string|max:50',
            'event_date' => 'required|date',
            'return_date' => 'nullable|date',
            'number_of_tables' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Tạo booking
        $booking = \App\Models\Booking::create([
            'customer_id' => $customer->customer_id,
            'created_by_user_id' => $user->user_id,
            'restaurant_id' => $request->restaurant_id,
            'hall_id' => $request->hall_id,
            'event_type' => $request->event_type,
            'event_time' => $request->event_time,
            'event_date' => $request->event_date,
            'return_date' => $request->return_date,
            'number_of_tables' => $request->number_of_tables,
            'price' => $request->price,
            'status' => $request->status ?? 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json($booking, 201);
    }


    // Lấy chi tiết booking
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        return response()->json($booking);
    }

    // Cập nhật booking
    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'created_by_user_id' => 'required|integer',
            'restaurant_id' => 'required|integer',
            'hall_id' => 'required|integer',
            'event_type' => 'required|string|max:255',
            'event_time' => 'required',
            'event_date' => 'required|date',
            'return_date' => 'nullable|date',
            'number_of_tables' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $booking = Booking::findOrFail($id);
        $booking->update($request->all());

        return response()->json($booking);
    }

    // Xóa booking
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully']);
    }
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
