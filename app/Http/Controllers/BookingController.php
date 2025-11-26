<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Booking;
use App\Models\Hall;
use App\Models\Food;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BookingController extends Controller
{
    // Lấy danh sách bookings, có thể filter theo restaurant, customer, status
    public function index(Request $request)
    {
        $query = Booking::with('hall'); // eager load hall để lấy hall_name

        if ($request->has('restaurant_id') && $request->restaurant_id != '') {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $bookings = $query->get();

        // map để thêm hall_name + price
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
                'price' => $b->price,
                'created_at' => $b->created_at,
            ];
        });

        return response()->json($bookings);
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


        // Validate dữ liệu: client không gửi `price`, server sẽ tính
        $request->validate([
            'restaurant_id' => 'required|integer|exists:restaurants,restaurant_id',
            'hall_id' => 'required|integer|exists:halls,hall_id',
            'event_type' => 'required|string|max:255',
            'event_time' => 'required|string|max:50',
            'event_date' => 'required|date',
            'return_date' => 'nullable|date',
            'number_of_tables' => 'required|integer|min:1',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'food_ids' => 'nullable|array',
            'food_ids.*' => 'integer|exists:foods,food_id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,service_id',
        ]);

        // Kiểm tra tính khả dụng: cùng hall_id + event_date + event_time (không tính các booking đã bị 'cancelled')
        try {
            $eventDateOnly = Carbon::parse($request->event_date)->toDateString();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ngày sự kiện không hợp lệ'], 422);
        }

        $conflictExists = Booking::where('hall_id', $request->hall_id)
            ->where('event_date', $eventDateOnly)
            ->where('event_time', $request->event_time)
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($conflictExists) {
            return response()->json(['message' => 'Sảnh đã được đặt vào thời gian này'], 409);
        }

        // Thực hiện trong transaction: tạo booking, lưu pivot, tính giá và cập nhật
        try {
            $booking = DB::transaction(function () use ($request, $customer, $user) {
                // Tạo booking tạm với price = 0
                $booking = Booking::create([
                    'customer_id' => $customer->customer_id,
                    'created_by_user_id' => $user->user_id,
                    'restaurant_id' => $request->restaurant_id,
                    'hall_id' => $request->hall_id,
                    'event_type' => $request->event_type,
                    'event_time' => $request->event_time,
                    'event_date' => $request->event_date,
                    'return_date' => $request->return_date,
                    'number_of_tables' => $request->number_of_tables,
                    'price' => 0,
                    'status' => $request->status ?? 'pending',
                    'notes' => $request->notes,
                ]);

                // Lưu các món ăn vào bảng pivot `booking_foods` nếu có
                $foodIds = $request->input('food_ids', []);
                if (is_string($foodIds)) {
                    $decoded = json_decode($foodIds, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $foodIds = $decoded;
                    } else {
                        // support comma-separated lists like "1,2,3"
                        if (strpos($foodIds, ',') !== false) {
                            $foodIds = array_map('trim', explode(',', $foodIds));
                        }
                    }
                }
                $foodIds = is_array($foodIds) ? array_values(array_filter($foodIds, function ($v) {
                    return $v !== null && $v !== '';
                })) : [];

                // If frontend sent detailed objects under `booking_foods`, extract ids
                if (empty($foodIds) && $request->has('booking_foods')) {
                    $bf = $request->input('booking_foods', []);
                    if (is_array($bf)) {
                        $extracted = [];
                        foreach ($bf as $obj) {
                            if (is_array($obj) && isset($obj['food_id'])) $extracted[] = $obj['food_id'];
                            elseif (is_object($obj) && isset($obj->food_id)) $extracted[] = $obj->food_id;
                        }
                        $foodIds = array_values(array_filter($extracted, function ($v) {
                            return $v !== null && $v !== '';
                        }));
                    }
                }

                // Debug log for parsed food ids
                Log::info('BookingController parsed food_ids', ['booking_tmp' => null, 'food_ids' => $foodIds]);

                if (count($foodIds) > 0) {
                    $rows = [];
                    foreach ($foodIds as $fid) {
                        $rows[] = [
                            'booking_id' => $booking->booking_id,
                            'food_id' => (int)$fid,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    $ok = DB::table('booking_foods')->insert($rows);
                    Log::info('BookingController inserted booking_foods', ['booking_id' => $booking->booking_id, 'count' => count($rows), 'ok' => $ok]);
                }

                // Lưu các dịch vụ vào bảng pivot `booking_services` nếu có
                $serviceIds = $request->input('service_ids', []);
                if (is_string($serviceIds)) {
                    $decoded = json_decode($serviceIds, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $serviceIds = $decoded;
                    } else {
                        if (strpos($serviceIds, ',') !== false) {
                            $serviceIds = array_map('trim', explode(',', $serviceIds));
                        }
                    }
                }
                $serviceIds = is_array($serviceIds) ? array_values(array_filter($serviceIds, function ($v) {
                    return $v !== null && $v !== '';
                })) : [];

                // If frontend sent detailed objects under `booking_services`, extract ids
                if (empty($serviceIds) && $request->has('booking_services')) {
                    $bs = $request->input('booking_services', []);
                    if (is_array($bs)) {
                        $extracted = [];
                        foreach ($bs as $obj) {
                            if (is_array($obj) && isset($obj['service_id'])) $extracted[] = $obj['service_id'];
                            elseif (is_object($obj) && isset($obj->service_id)) $extracted[] = $obj->service_id;
                        }
                        $serviceIds = array_values(array_filter($extracted, function ($v) {
                            return $v !== null && $v !== '';
                        }));
                    }
                }

                // Debug log for parsed service ids
                Log::info('BookingController parsed service_ids', ['booking_tmp' => null, 'service_ids' => $serviceIds]);

                if (count($serviceIds) > 0) {
                    $rows = [];
                    foreach ($serviceIds as $sid) {
                        $rows[] = [
                            'booking_id' => $booking->booking_id,
                            'service_id' => (int)$sid,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    $ok = DB::table('booking_services')->insert($rows);
                    Log::info('BookingController inserted booking_services', ['booking_id' => $booking->booking_id, 'count' => count($rows), 'ok' => $ok]);
                }

                // Tính tổng giá
                $hall = Hall::find($request->hall_id);
                $hallPrice = $hall && $hall->price ? (float)$hall->price : 0.0;

                $totalFoodPrice = 0.0;
                if (count($foodIds) > 0) {
                    $totalFoodPrice = (float) Food::whereIn('food_id', $foodIds)->sum('price');
                }

                $totalServicePrice = 0.0;
                if (count($serviceIds) > 0) {
                    $totalServicePrice = (float) Service::whereIn('service_id', $serviceIds)->sum('price');
                }

                $computedPrice = $hallPrice + ((int)$request->number_of_tables * $totalFoodPrice) + $totalServicePrice;

                // Cập nhật booking với giá tính được
                $booking->price = $computedPrice;
                $booking->save();

                return $booking;
            });
        } catch (\Exception $e) {
            Log::error('Booking create failed: ' . $e->getMessage());
            return response()->json(['message' => 'Tạo booking thất bại'], 500);
        }

        // Trả về booking cùng các món và dịch vụ đã lưu để frontend dễ kiểm tra
        $booking->load('foods', 'services', 'hall');
        return response()->json($booking, 201);
    }


    // Lấy chi tiết booking
    public function show($id)
    {
        $booking = Booking::with('hall')->findOrFail($id);
        return response()->json([
            'booking_id' => $booking->booking_id,
            'customer_id' => $booking->customer_id,
            'created_by_user_id' => $booking->created_by_user_id,
            'restaurant_id' => $booking->restaurant_id,
            'hall_id' => $booking->hall_id,
            'hall_name' => $booking->hall ? $booking->hall->name : null,
            'event_type' => $booking->event_type,
            'event_time' => $booking->event_time,
            'event_date' => $booking->event_date,
            'return_date' => $booking->return_date,
            'number_of_tables' => $booking->number_of_tables,
            'status' => $booking->status,
            'notes' => $booking->notes,
            'price' => $booking->price,
            'created_at' => $booking->created_at,
        ]);
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

        $bookings = Booking::with('hall')
            ->where('created_by_user_id', $userId)
            ->orderBy('event_date', 'desc')
            ->get();

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
                'price' => $b->price,
                'created_at' => $b->created_at,
            ];
        });

        return response()->json($bookings);
    }
}
