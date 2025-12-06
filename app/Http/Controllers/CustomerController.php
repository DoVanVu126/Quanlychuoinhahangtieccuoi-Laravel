<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Lấy danh sách khách hàng cho trang Customer.vue
     */
    public function index(Request $request)
    {
        // 1. Khởi tạo query và Eager Load quan hệ 'user' để tránh N+1 Query
        $query = Customer::with('user');

        // 2. Xử lý tìm kiếm
        // Vue gửi params: ?search=...
        if ($search = $request->input('search')) {
            // Tìm kiếm trong bảng users dựa trên quan hệ
            $query->whereHas('user', function($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }

        // 3. Sắp xếp: Khách hàng mới nhất lên đầu
        $query->orderBy('created_at', 'desc');

        // 4. Phân trang
        // Vue gửi params: ?per_page=10
        $perPage = $request->input('per_page', 10);
        $customers = $query->paginate($perPage);

        // 5. Transform dữ liệu (QUAN TRỌNG)
        // Biến đổi cấu trúc nested (customer.user.email) thành cấu trúc phẳng (customer.email)
        // để khớp với code map() bên Vue.
        $customers->getCollection()->transform(function ($customer) {
            return [
                'customer_id' => $customer->customer_id,
                'user_id'     => $customer->user_id,
                'created_at'  => $customer->created_at,
                // Lấy thông tin từ bảng user nhét ra ngoài
                'username'    => $customer->user->username ?? 'N/A',
                'full_name'   => $customer->user->full_name ?? '',
                'email'       => $customer->user->email ?? '',
                'phone'       => $customer->user->phone ?? '',
                'image_url'   => $customer->user->image_url ?? '',
                'address'     => $customer->user->address ?? '',
            ];
        });

        return response()->json($customers);
    }

    /**
     * Xem chi tiết khách hàng (Cho function goToCustomerDetail)
     */
    public function show($id)
    {
        $customer = Customer::with(['user', 'bookings'])->findOrFail($id);

        // Trả về dữ liệu chi tiết kèm lịch sử đặt tiệc
        return response()->json([
            'customer_info' => [
                'customer_id' => $customer->customer_id,
                'full_name'   => $customer->user->full_name,
                'email'       => $customer->user->email,
                'phone'       => $customer->user->phone,
                'address'     => $customer->user->address,
                'image_url'   => $customer->user->image_url,
            ],
            'bookings' => $customer->bookings // Danh sách đơn đặt tiệc
        ]);
    }

    //Xem chi tiết khách hàng: đặt tiệc + thanh toán
    public function getDetails($id)
    {
        // 1. Lấy thông tin Customer & User
        // Sử dụng findOrFail để trả về 404 nếu không tìm thấy
        $customer = Customer::with('user')->findOrFail($id);

        // 2. Lấy danh sách Bookings của khách này
        // Eager load 'hall' để lấy tên sảnh hiển thị lên Vue
        $bookingsData = Booking::with('hall')
            ->where('customer_id', $id)
            ->orderBy('event_date', 'desc')
            ->get();

        // 3. Map dữ liệu Booking (Backend -> Frontend)
        // Vue đang mong đợi các trường: id, event_type, hall_name, total_amount...
        $bookings = $bookingsData->map(function ($b) {
            return [
                'id'           => $b->booking_id,
                'event_type'   => $b->event_type,
                'event_date'   => $b->event_date, 
                'hall_name'    => $b->hall ? $b->hall->name : 'Chưa chọn sảnh',
                'status'       => $b->status,
                
                // Mapping quan trọng: DB là 'price', Vue đang gọi là 'total_amount'
                'total_amount' => $b->price, 
            ];
        });

        // 4. Lấy danh sách Payments
        // Tìm tất cả payment thuộc các booking của khách này
        $bookingIds = $bookingsData->pluck('booking_id');
        
        $payments = Payment::whereIn('booking_id', $bookingIds)
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id'               => $p->payment_id,
                    'booking_id'       => $p->booking_id,
                    'transaction_code' => $p->transaction_code,
                    
                    // Mapping quan trọng: DB là 'total_amount', Vue đang gọi là 'amount'
                    'amount'           => $p->total_amount, 
                    
                    'payment_method'   => $p->payment_method,
                    'payment_date'     => $p->payment_date,
                    'status'           => $p->payment_status // paid/unpaid/partial
                ];
            });

        // 5. Chuẩn bị thông tin Customer (Làm phẳng dữ liệu user)
        $customerInfo = [
            'id'        => $customer->customer_id,
            'username'  => $customer->user->username ?? '',
            'email'     => $customer->user->email ?? '',
            'phone'     => $customer->user->phone ?? '',
            'address'   => $customer->user->address ?? '',
            'full_name' => $customer->user->full_name ?? 'Chưa cập nhật tên',
        ];

        // 6. Trả về JSON tổng hợp
        return response()->json([
            'customer' => $customerInfo,
            'bookings' => $bookings,
            'payments' => $payments
        ]);
    }
}