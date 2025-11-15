<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    // 🔹 Lấy danh sách tất cả nhà hàng
    public function index()
    {
        return response()->json(Restaurant::all());
    }

    // 🔹 Thêm nhà hàng mới
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'ward' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'capacity' => 'nullable|integer',
            'price_table' => 'nullable|numeric',
            'star_rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $restaurant = Restaurant::create($request->all());
        return response()->json($restaurant, 201);
    }

    // 🔹 Xem chi tiết 1 nhà hàng
    public function show($id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Không tìm thấy nhà hàng'], 404);
        }
        return response()->json($restaurant);
    }

    // 🔹 Cập nhật nhà hàng
    public function update(Request $request, $id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Không tìm thấy nhà hàng'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'ward' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'capacity' => 'nullable|integer',
            'price_table' => 'nullable|numeric',
            'star_rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $restaurant->update($request->all());
        return response()->json($restaurant);
    }

    // 🔹 Xóa nhà hàng
    public function destroy($id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Không tìm thấy nhà hàng'], 404);
        }

        $restaurant->delete();
        return response()->json(['message' => 'Xóa nhà hàng thành công']);
    }

    // 🔹 Top nhà hàng theo sao
    public function topRestaurants()
    {
        $restaurants = Restaurant::orderBy('star_rating', 'desc')
            ->take(10)
            ->get();

        return response()->json($restaurants);
    }

    // 🔹 Tìm kiếm nhà hàng theo tên hoặc địa chỉ (ward, city)
    public function search(Request $request)
    {
        $keyword = trim($request->query('keyword', ''));

        // ✅ Nếu không có keyword: trả về nhà hàng nổi bật
        if ($keyword === '') {
            return response()->json(
                Restaurant::orderByDesc('star_rating')
                    ->limit(50) // Giới hạn 50 kết quả cho nhẹ
                    ->get()
            );
        }

        // ✅ Chuẩn hóa keyword: bỏ ký tự đặc biệt, chuyển chữ thường
        $keyword = preg_replace('/[^a-zA-Z0-9\sÀ-ỹ]/u', '', strtolower($keyword));

        // ✅ Tách các từ khóa nhỏ (ví dụ: "Phường 6 Hà Nội" → ["phường", "6", "hà", "nội"])
        $keywords = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);

        // ✅ Khởi tạo query
        $query = Restaurant::query();

        // ✅ Áp dụng tìm kiếm theo từng từ khóa
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $q->where(function ($sub) use ($word) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(ward) LIKE ?', ["%{$word}%"])
                        ->orWhereRaw('LOWER(city) LIKE ?', ["%{$word}%"]);
                });
            }
        });

        // ✅ Sắp xếp thông minh hơn:
        // 1. Đánh giá cao sao
        // 2. Nhà hàng có tên chứa nguyên keyword được ưu tiên
        $query->orderByDesc('star_rating')
            ->orderByRaw("CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END", ["%{$keyword}%"]);

        $restaurants = $query->limit(100)->get(); // ✅ Giới hạn 100 kết quả

        return response()->json($restaurants);
    }
}
