<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FoodTypeController;
use App\Http\Controllers\SuggestionPackageController;
use App\Http\Controllers\UserPromotionController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\DashboardController;


use App\Http\Controllers\ReviewController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // 🔔 QUẢN LÝ THÔNG BÁO (NOTIFICATIONS)
    // ==========================================
    
    // Lấy danh sách (Frontend gọi: GET /api/notifications)
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Đánh dấu 1 cái đã đọc
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    // Đánh dấu tất cả đã đọc
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Xóa 1 thông báo
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);

    // Xóa tất cả thông báo
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll']);

    //Lấy thống kê cho dashboard
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
    // ==========================================
    // 🎫 QUẢN LÝ YÊU CẦU HỖ TRỢ (SUPPORT TICKETS)
    // ==========================================

    // 1. Khách hàng gửi yêu cầu mới (Frontend SupportCustomer.vue gọi)
    Route::post('/support-tickets', [SupportTicketController::class, 'store']);

    // 2. Admin lấy danh sách (Frontend SupportTickets.vue gọi)
    Route::get('/support-tickets', [SupportTicketController::class, 'index']);

    // 3. Admin cập nhật trạng thái nhanh (Dropdown status)
    Route::put('/support-tickets/{id}', [SupportTicketController::class, 'update']);

    // 4. Admin trả lời & Tạo thông báo (Frontend SupportTickets.vue gọi nút "Gửi phản hồi")
    // ⚠️ QUAN TRỌNG: Đây là route còn thiếu của bạn
    Route::post('/support-tickets/{id}/reply', [SupportTicketController::class, 'reply']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);


// Xác thực người dùng 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Đặt lại mật khẩu
Route::prefix('password-reset')->group(function () {
    Route::post('/send-otp', [PasswordResetController::class, 'sendOtp']);
    Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp']);
    Route::post('/reset', [PasswordResetController::class, 'resetPassword']);
});

// Hồ sơ người dùng
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);
    //Upload avatar
    Route::post('/users/{id}/avatar', [ProfileController::class, 'uploadAvatar']);
    // Lấy thông tin (GET)
    Route::get('/users/{id}', [ProfileController::class, 'show']); 
    
    // Cập nhật thông tin (PUT) -> Đây là cái bạn đang lỗi
    Route::put('/users/{id}', [ProfileController::class, 'update']);
    
    // Xóa tài khoản (DELETE)
    Route::delete('/users/{id}', [ProfileController::class, 'destroy']);
    
    // Upload avatar (POST)
    Route::post('/users/{id}', [ProfileController::class, 'updateAvatar']); // Nếu bạn có hàm updateAvatar riêng
    Route::put('/changePassword', [ProfileController::class, 'changePassword']);
    
});
Route::get('/booking-history', [BookingController::class, 'BookingbyUser']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);

// Customer routes
Route::get('/customers', [CustomerController::class, 'index']);
Route::get('/customers/{id}', [CustomerController::class, 'show']);
// Route lấy chi tiết khách hàng
Route::get('/customers/{id}/details', [CustomerController::class, 'getDetails']);

Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/users/export/pdf', [UserController::class, 'exportPDF'])->name('users.export.pdf');

// danh sách dịch vụ
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::post('/services', [ServiceController::class, 'store']);
Route::put('/services/{id}', [ServiceController::class, 'update']);
Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
Route::get('/restaurants/{id}/services', [ServiceController::class, 'getServicesByRestaurant']);

//Food
Route::get('/foods/export-pdf', [FoodController::class, 'exportPDF']);
Route::get('/foods', [FoodController::class, 'index']);
Route::get('/foods/{id}', [FoodController::class, 'show']);
Route::post('/foods', [FoodController::class, 'store']);
Route::put('/foods/{id}', [FoodController::class, 'update']);
Route::delete('/foods/{id}', [FoodController::class, 'destroy']);
Route::get('/restaurants/{id}/foods', [FoodController::class, 'getFoodsByRestaurant']);
Route::get('/food-types', [FoodTypeController::class, 'index']);


// ================== API DANH SÁCH THÀNH PHỐ ==================
Route::get('/restaurants/city', function () {
    $cities = DB::table('restaurants')
        ->select('city')
        ->distinct()
        ->whereNotNull('city')
        ->pluck('city');
    return response()->json(['cities' => $cities]);
});


// ================== API DANH SÁCH PHƯỜNG / XÃ THEO THÀNH PHỐ ==================
Route::get('/restaurants/ward', function (Request $request) {
    $city = trim($request->query('city'));

    $wards = DB::table('restaurants')
        ->select('ward')
        ->whereRaw('LOWER(city) = ?', [strtolower($city)])
        ->whereNotNull('ward')
        ->distinct()
        ->pluck('ward');

    return response()->json(['wards' => $wards]);
});

//Nhà hàng
Route::get('/restaurants/paginated', [RestaurantController::class, 'paginated']); // đặt trước {id}
Route::get('/restaurants/search', [RestaurantController::class, 'search']);       // đặt trước {id}
Route::get('/restaurants', [RestaurantController::class, 'index']);               // lấy tất cả
Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);           // route {id} cuối cùng

Route::post('/restaurants', [RestaurantController::class, 'store']);
Route::put('/restaurants/{id}', [RestaurantController::class, 'update']);
Route::delete('/restaurants/{id}', [RestaurantController::class, 'destroy']);

Route::get('/top-restaurants', [RestaurantController::class, 'topRestaurants']);


// 🏛️ Sảnh tiệc (Hall)
Route::get('/halls', [HallController::class, 'index']);
Route::get('/halls/{id}', [HallController::class, 'show']);
Route::post('/halls', [HallController::class, 'store']);
Route::put('/halls/{id}', [HallController::class, 'update']);
Route::delete('/halls/{id}', [HallController::class, 'destroy']);
// Lấy danh sách sảnh theo restaurant_id
Route::get('/restaurants/{id}/halls', [HallController::class, 'getHallsByRestaurant']);

// Suggestion packages (Gợi ý)
Route::get('/suggestion-packages', [SuggestionPackageController::class, 'index']);
Route::get('/suggestion-packages/{id}', [SuggestionPackageController::class, 'show']);
Route::get('/restaurants/{id}/suggestion-packages', [SuggestionPackageController::class, 'byRestaurant']);

// 📦 Kho hàng (Inventory)
Route::get('/inventories', [InventoryController::class, 'index']);
Route::get('/inventories/low-stock', [InventoryController::class, 'lowStock']);
Route::get('/inventories/{id}', [InventoryController::class, 'show']);
Route::post('/inventories', [InventoryController::class, 'store']);
Route::put('/inventories/{id}', [InventoryController::class, 'update']);
Route::post('/inventories/{id}/quantity', [InventoryController::class, 'updateQuantity']);
Route::delete('/inventories/{id}', [InventoryController::class, 'destroy']);

// 💰 Thanh toán (Payments)
Route::get('/payments', [PaymentController::class, 'index']);
Route::get('/payments/statistics', [PaymentController::class, 'statistics']);
Route::get('/payments/{id}', [PaymentController::class, 'show']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::put('/payments/{id}', [PaymentController::class, 'update']);
Route::post('/payments/{id}/status', [PaymentController::class, 'updateStatus']);
Route::post('/payments/{id}/add-payment', [PaymentController::class, 'addPayment']);
Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);

// Khuyến mãi
Route::get('/promotions/all', [PromotionController::class, 'getAll']);   // người dùng
Route::get('/promotions', [PromotionController::class, 'index']);
Route::get('/promotions/{id}', [PromotionController::class, 'show']);
Route::post('/promotions', [PromotionController::class, 'store']);
Route::post('/promotions/{id}', [PromotionController::class, 'update']); // POST thay vì PUT để upload ảnh dễ dàng
Route::delete('/promotions/{id}', [PromotionController::class, 'destroy']);
Route::get('/promotions/check', [PromotionController::class, 'checkCode']);



// Bookings
Route::get('/bookings/user', [BookingController::class, 'BookingbyUser']);
Route::get('/bookings', [BookingController::class, 'index']);
Route::get('/bookings/{id}', [BookingController::class, 'show']);
Route::post('/bookings', [BookingController::class, 'store'])->middleware('auth:sanctum');

Route::put('/bookings/{id}', [BookingController::class, 'update']);
Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);


// Notification
Route::get('/notifications/{user_id}', [NotificationController::class, 'index']);
Route::post('/notifications', [NotificationController::class, 'store']);

Route::put('/notifications/read/{id}', [NotificationController::class, 'markRead']);
Route::put('/notifications/read-all/{user_id}', [NotificationController::class, 'markAllRead']);

Route::delete('/notifications/{id}', [NotificationController::class, 'delete']);
Route::delete('/notifications/user/{user_id}', [NotificationController::class, 'deleteAll']);
Route::post('/notifications/send-toast', [NotificationController::class, 'sendToast']);


// khuyến mãi-user
Route::post('/user-promotions', [UserPromotionController::class, 'store']);
Route::get('/user-promotions', [UserPromotionController::class, 'index']);
Route::delete('/user-promotions/{id}', [UserPromotionController::class, 'destroy']);


//Membership
// Lấy thông tin membership
Route::get('/membership/{user_id}', [MembershipController::class, 'show'])->name('membership.show');

// Cập nhật membership sau booking confirmed
Route::post('/membership/booking/{booking_id}', [MembershipController::class, 'updateAfterBooking'])->name('membership.updateAfterBooking');


// đánh giá
// API chi tiết nhà hàng (frontend)
Route::get('/reviews/{restaurantId}', [ReviewController::class, 'index']);

// Admin review
Route::get('/reviews', [ReviewController::class, 'index']); // paginate + filter
Route::post('/reviews', [ReviewController::class, 'store']);
Route::put('/reviews/{id}', [ReviewController::class, 'update']);
Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
