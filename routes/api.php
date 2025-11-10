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



//Food
Route::get('/foods', [FoodController::class, 'index']);
Route::get('/foods/{id}', [FoodController::class, 'show']);
Route::post('/foods', [FoodController::class, 'store']);
Route::put('/foods/{id}', [FoodController::class, 'update']);
Route::delete('/foods/{id}', [FoodController::class, 'destroy']);

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
Route::get('/restaurants/search', [RestaurantController::class, 'search']);
Route::get('/restaurants', [RestaurantController::class, 'index']);
Route::get('/restaurants/{id}', [RestaurantController::class, 'show']);
Route::post('/restaurants', [RestaurantController::class, 'store']);
Route::put('/restaurants/{id}', [RestaurantController::class, 'update']);
Route::delete('/restaurants/{id}', [RestaurantController::class, 'destroy']);

// 🏛️ Sảnh tiệc (Hall)
Route::get('/halls', [HallController::class, 'index']);
Route::get('/halls/{id}', [HallController::class, 'show']);
Route::post('/halls', [HallController::class, 'store']);
Route::put('/halls/{id}', [HallController::class, 'update']);
Route::delete('/halls/{id}', [HallController::class, 'destroy']);

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
