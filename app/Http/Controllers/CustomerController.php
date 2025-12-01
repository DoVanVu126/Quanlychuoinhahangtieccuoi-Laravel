<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * 1. Lấy danh sách khách hàng (ĐÃ ĐẶT TIỆC)
     * Kèm tìm kiếm & phân trang
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // QUERY BUILDER:
        // 1. Từ bảng customers
        // 2. JOIN với users (để lấy tên, email...)
        // 3. JOIN với bookings (để lọc chỉ lấy người ĐÃ ĐẶT)
        $query = Customer::join('users', 'customers.user_id', '=', 'users.user_id')
            ->join('bookings', 'customers.customer_id', '=', 'bookings.customer_id') 
            ->select(
                'customers.customer_id',
                'customers.user_id',
                'customers.created_at', 
                'users.username',
                'users.email',
                'users.phone',
                'users.full_name',
                'users.address',
                'users.image_url',
                'users.role',
                'users.created_at as user_created_at'
            )
            ->distinct(); // Quan trọng: Loại bỏ trùng lặp (nếu khách đặt 2 lần)

        // Xử lý Tìm kiếm
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('users.username', 'LIKE', "%{$search}%")
                  ->orWhere('users.email', 'LIKE', "%{$search}%")
                  ->orWhere('users.full_name', 'LIKE', "%{$search}%")
                  ->orWhere('users.phone', 'LIKE', "%{$search}%");
            });
        }

        // Sắp xếp: Ưu tiên khách hàng mới đặt gần đây nhất
        // (Lưu ý: cần cẩn thận khi order với distinct, nên order theo customer.created_at cho an toàn)
        $customers = $query->orderBy('customers.created_at', 'desc')->paginate($perPage);

        return response()->json($customers);
    }

    /**
     * 2. Lấy chi tiết khách hàng + Lịch sử đặt tiệc + Thanh toán
     */
    public function showDetails($id)
    {
        // ... (Phần lấy $customer giữ nguyên) ...
        // ... (Phần lấy $bookings giữ nguyên) ...

        // A. Tìm thông tin khách hàng
        $customer = Customer::join('users', 'customers.user_id', '=', 'users.user_id')
            ->where('customers.customer_id', $id)
            ->select(
                'customers.customer_id',
                'customers.user_id',
                'users.username',
                'users.email',
                'users.phone',
                'users.full_name'
            )
            ->first();

        if (!$customer) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }

        // B. Lấy lịch sử đặt tiệc (Bookings)
        $bookings = Booking::leftJoin('halls', 'bookings.hall_id', '=', 'halls.hall_id')
            ->where('bookings.customer_id', $id)
            ->select(
                'bookings.booking_id as id',
                'bookings.event_type',
                'bookings.event_date',
                'bookings.status',
                'bookings.price as total_amount',
                'halls.name as hall_name'
            )
            ->orderBy('bookings.created_at', 'desc')
            ->get();

        // C. Lấy lịch sử thanh toán (Payments) - CẬP NHẬT PHẦN NÀY
        $bookingIds = $bookings->pluck('id'); // Lấy danh sách booking_id
        
        $payments = Payment::whereIn('booking_id', $bookingIds)
            ->select(
                'payment_id as id',
                'transaction_code',
                'booking_id',
                'total_amount as amount',       // <-- Đổi tên cho khớp Frontend
                'payment_method',
                'payment_date',
                'payment_status as status'      // <-- Đổi tên cho khớp Frontend
            )
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'customer' => $customer,
            'bookings' => $bookings,
            'payments' => $payments
        ]);
    }
}