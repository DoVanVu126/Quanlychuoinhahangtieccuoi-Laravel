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
Route::post('/login', [UserController::class, 'login']);



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::get('/users/{id}', [UserController::class, 'show']);

// danh sách dịch vụ
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::post('/services', [ServiceController::class, 'store']);
Route::put('/services/{id}', [ServiceController::class, 'update']);
Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
Route::get('/restaurants/{id}/services', [ServiceController::class, 'getServicesByRestaurant']);

//Food
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
Route::get('/top-restaurants', [RestaurantController::class, 'topRestaurants']);
Route::get('/restaurants/paginated', [RestaurantController::class, 'paginated']); // đặt trước {id}
Route::get('/restaurants/search', [RestaurantController::class, 'search']);       // đặt trước {id}
Route::get('/restaurants', [RestaurantController::class, 'index']);               // lấy tất cả
Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);           // route {id} cuối cùng

Route::post('/restaurants', [RestaurantController::class, 'store']);
Route::put('/restaurants/{id}', [RestaurantController::class, 'update']);
Route::delete('/restaurants/{id}', [RestaurantController::class, 'destroy']);

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
Route::get('/notifications/{userId}', [NotificationController::class, 'getNotifications']);
Route::post('/notifications/send', [NotificationController::class, 'sendNotification']);
Route::patch('/notifications/read/{id}', [NotificationController::class, 'markRead']);
Route::patch('/notifications/read-all/{userId}', [NotificationController::class, 'markAllRead']);
// Xóa 1 thông báo
Route::delete('/notifications/{id}', [NotificationController::class, 'deleteNotif']);

// Xóa tất cả thông báo của user
Route::delete('/notifications/delete-all/{userId}', [NotificationController::class, 'deleteAll']);
Route::post('/notifications/send-toast', [NotificationController::class, 'sendToast']);


// khuyến mãi-user
Route::post('/user-promotions', [UserPromotionController::class, 'store']);
Route::get('/user-promotions', [UserPromotionController::class, 'index']);
Route::delete('/user-promotions/{id}', [UserPromotionController::class, 'destroy']);
