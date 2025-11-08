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

    if ($keyword === '') {
        return response()->json(Restaurant::orderBy('star_rating', 'desc')->get());
    }

    // Tách chuỗi thành các từ nhỏ: "Phường 6 Hà Nội" => ["Phường", "6", "Hà", "Nội"]
    $keywords = preg_split('/\s+/', $keyword);

    $restaurants = Restaurant::query();

    // Tìm theo từng từ
    $restaurants->where(function ($query) use ($keywords) {
        foreach ($keywords as $word) {
            $query->orWhere('name', 'like', "%{$word}%")
                  ->orWhere('ward', 'like', "%{$word}%")
                  ->orWhere('city', 'like', "%{$word}%");
        }
    });

    $restaurants = $restaurants->orderBy('star_rating', 'desc')->get();

    return response()->json($restaurants);
}

}
