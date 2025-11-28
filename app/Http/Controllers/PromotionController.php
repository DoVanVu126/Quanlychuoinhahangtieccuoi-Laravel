<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    // 📌 Lấy danh sách khuyến mãi
    public function index(Request $request)
    {
        $query = Promotion::with('restaurant');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('promotion_code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        $promotions = $query->orderBy('promotion_id', 'asc')->paginate(10);

        // Chuẩn hóa URL ảnh
        $promotions->getCollection()->transform(function ($promo) {
            if ($promo->image_url && !preg_match('/^https?:\/\//', $promo->image_url)) {
                $promo->image_url = asset($promo->image_url);
            }
            return $promo;
        });

        return response()->json($promotions);
    }

    // 📌 API cho giao diện người dùng (KHÔNG phân trang)
    public function getAll()
    {
        $promotions = Promotion::orderBy('promotion_id', 'desc')->get();

        $promotions->transform(function ($promo) {
            // Nếu có ảnh, chuẩn hóa URL
            if ($promo->image) {
                $promo->image = preg_match('/^https?:\/\//', $promo->image)
                    ? $promo->image
                    : asset($promo->image);
            } else {
                // Không có ảnh → trả về ảnh mặc định
                $promo->image = asset('img/default.jpg');
            }
            return $promo;
        });

        return response()->json([
            'data' => $promotions
        ]);
    }

    public function checkCode(Request $request)
    {
        $code = $request->code;

        $promo = Promotion::where('promotion_code', $code)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'promotion' => $promo
        ]);
    }

    // 📌 Xem chi tiết khuyến mãi
    public function show($id)
    {
        $promo = Promotion::with('restaurant')->findOrFail($id);

        if ($promo->image_url && !preg_match('/^https?:\/\//', $promo->image_url)) {
            $promo->image_url = asset($promo->image_url);
        }

        return response()->json($promo);
    }

    // 📌 Thêm mới khuyến mãi
    public function store(Request $request)
    {
        // Validate dữ liệu
        $validated = $request->validate([
            'restaurant_id'   => 'required|integer',
            'promotion_code'  => 'required|unique:promotions|max:50',
            'title'           => 'required|max:100',
            'description'     => 'nullable|max:255',
            'discount_type'   => 'required|in:percent,amount',
            'discount_value'  => 'required|numeric',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'status'          => 'nullable|in:active,expired,upcoming',
            'image'           => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        // Nếu có upload ảnh
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();

            // Lưu file vào folder public/uploads/promotions
            $file->move(public_path('uploads/promotions'), $fileName);

            // Lưu đường dẫn relative vào database đúng field 'image'
            $validated['image'] = 'uploads/promotions/' . $fileName;
        }

        // Tạo mới promotion
        $promo = Promotion::create($validated);

        // Trả về URL đầy đủ cho frontend hiển thị ảnh
        if ($promo->image) {
            $promo->image = asset($promo->image);
        }

        return response()->json($promo, 201);
    }



    // 📌 Cập nhật khuyến mãi
    public function update(Request $request, $id)
    {
        $promo = Promotion::findOrFail($id);

        $validated = $request->validate([
            'restaurant_id' => 'sometimes|required|integer',
            'promotion_code' => 'sometimes|required|max:50|unique:promotions,promotion_code,' . $id . ',promotion_id',
            'title' => 'sometimes|required|max:100',
            'description' => 'nullable|max:255',
            'discount_type' => 'sometimes|required|in:percent,amount',
            'discount_value' => 'sometimes|required|numeric',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'status' => 'nullable|in:active,expired,upcoming',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Nếu có ảnh mới → xóa ảnh cũ + lưu ảnh mới
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu tồn tại
            if ($promo->image && file_exists(public_path($promo->image))) {
                unlink(public_path($promo->image));
            }

            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/promotions'), $fileName);
            $validated['image'] = 'uploads/promotions/' . $fileName; // lưu tên file relative
        }

        $promo->update($validated);

        // Trả về URL đầy đủ
        if ($promo->image) {
            $promo->image_url = asset($promo->image);
        }

        return response()->json($promo);
    }

    // 📌 Xóa khuyến mãi
    public function destroy($id)
    {
        $promo = Promotion::findOrFail($id);

        // Xóa file ảnh nếu tồn tại
        if ($promo->image && file_exists(public_path($promo->image))) {
            unlink(public_path($promo->image));
        }

        // Xóa bản ghi
        $promo->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
   // PromotionController.php
public function recommend(Request $request)
{
    $userId = $request->query('user_id');
    if (!$userId) {
        return response()->json(['message' => 'user_id is required'], 400);
    }

    // Lấy lịch sử booking của user
    $bookings = DB::table('bookings')
        ->where('created_by_user_id', $userId)
        ->get();

    // Nếu user chưa booking lần nào → trả 10 promotion mới nhất
    if ($bookings->isEmpty()) {
        $promos = Promotion::orderBy('promotion_id', 'desc')->take(10)->get();
        $promos->transform(fn($promo) => [
            'promotion_id' => $promo->promotion_id,
            'restaurant_id' => $promo->restaurant_id,
            'promotion_code' => $promo->promotion_code,
            'title' => $promo->title,
            'description' => $promo->description,
            'image' => $promo->image ? asset($promo->image) : asset('img/default.jpg'),
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
            'start_date' => $promo->start_date,
            'end_date' => $promo->end_date,
            'status' => $promo->status,
        ]);
        return response()->json(['data' => $promos]);
    }

    // Nếu có booking → tính sở thích
    $topRestaurant = $bookings->groupBy('restaurant_id')
        ->sortByDesc(fn($group) => $group->count())
        ->keys()
        ->first();

    $topTheme = $bookings->groupBy('event_type')
        ->sortByDesc(fn($group) => $group->count())
        ->keys()
        ->first();

    // Lấy promotion gợi ý dựa theo sở thích user
    $promos = Promotion::where(function ($q) use ($topRestaurant, $topTheme) {
        if ($topRestaurant) $q->orWhere('restaurant_id', $topRestaurant);
        if ($topTheme) {
            $q->orWhere('title', 'LIKE', "%{$topTheme}%")
              ->orWhere('description', 'LIKE', "%{$topTheme}%");
        }
    })
    ->orderBy('promotion_id', 'desc')
    ->take(10)
    ->get();

    // Nếu vẫn rỗng → fallback 10 promotion mới nhất
    if ($promos->isEmpty()) {
        $promos = Promotion::orderBy('promotion_id', 'desc')->take(10)->get();
    }

    // Chuẩn hóa URL ảnh
    $promos->transform(fn($promo) => [
        'promotion_id' => $promo->promotion_id,
        'restaurant_id' => $promo->restaurant_id,
        'promotion_code' => $promo->promotion_code,
        'title' => $promo->title,
        'description' => $promo->description,
        'image' => $promo->image ? asset($promo->image) : asset('img/default.jpg'),
        'discount_type' => $promo->discount_type,
        'discount_value' => $promo->discount_value,
        'start_date' => $promo->start_date,
        'end_date' => $promo->end_date,
        'status' => $promo->status,
    ]);

    return response()->json(['data' => $promos]);
}

}
