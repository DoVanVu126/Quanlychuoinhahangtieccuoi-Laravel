<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    // 📌 Lấy danh sách nhà hàng (có phân trang)
    public function index()
    {
        $restaurants = Restaurant::all(); // Lấy tất cả nhà hàng

        // Chuẩn hóa URL ảnh
        $restaurants = $restaurants->map(function ($r) {
            if ($r->image_url && !preg_match('/^https?:\/\//', $r->image_url)) {
                $r->image_url = asset(trim($r->image_url, '/'));
            }
            return $r;
        });

        return response()->json($restaurants);
    }

    public function paginated(Request $request)
    {
        $perPage = $request->query('per_page', 10); // Số item / trang
        $restaurants = Restaurant::paginate($perPage);

        // Chuẩn hóa URL ảnh
        $restaurants->setCollection(
            $restaurants->getCollection()->map(function ($r) {
                if ($r->image_url && !preg_match('/^https?:\/\//', $r->image_url)) {
                    $r->image_url = asset(trim($r->image_url, '/'));
                }
                return $r;
            })
        );

        return response()->json($restaurants);
    }

    // 📌 Lấy chi tiết nhà hàng
    public function show($id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Nhà hàng không tồn tại'], 404);
        }

        if ($restaurant->image_url && !preg_match('/^https?:\/\//', $restaurant->image_url)) {
            $restaurant->image_url = asset(trim($restaurant->image_url, '/'));
        }

        return response()->json($restaurant);
    }

    // 📌 Thêm nhà hàng
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:restaurants,name',
            'description' => 'nullable|string',
            'ward' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'capacity' => 'nullable|integer',
            'price_table' => 'nullable|numeric',
            'star_rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/restaurants'), $fileName);
            $validated['image_url'] = 'uploads/restaurants/' . $fileName;
        }

        $restaurant = Restaurant::create($validated);

        // Chuẩn hóa URL ảnh
        if ($restaurant->image_url) {
            $restaurant->image_url = asset($restaurant->image_url);
        }

        return response()->json($restaurant, 201);
    }

    // 📌 Cập nhật nhà hàng
    public function update(Request $request, $id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Nhà hàng không tồn tại'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:restaurants,name,' . $id . ',restaurant_id',
            'description' => 'nullable|string',
            'ward' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'capacity' => 'nullable|integer',
            'price_table' => 'nullable|numeric',
            'star_rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Upload ảnh mới nếu có → xóa ảnh cũ
        if ($request->hasFile('image')) {
            if ($restaurant->image_url && file_exists(public_path($restaurant->image_url))) {
                unlink(public_path($restaurant->image_url));
            }

            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/restaurants'), $fileName);
            $validated['image_url'] = 'uploads/restaurants/' . $fileName;
        }

        $restaurant->update($validated);

        if ($restaurant->image_url) {
            $restaurant->image_url = asset($restaurant->image_url);
        }

        return response()->json($restaurant);
    }

    // 📌 Xóa nhà hàng
    public function destroy($id)
    {
        $restaurant = Restaurant::find($id);
        if (!$restaurant) {
            return response()->json(['message' => 'Nhà hàng không tồn tại'], 404);
        }

        // Xóa ảnh nếu có
        if ($restaurant->image_url && file_exists(public_path($restaurant->image_url))) {
            unlink(public_path($restaurant->image_url));
        }

        $restaurant->delete();

        return response()->json(['message' => 'Xóa nhà hàng thành công']);
    }
    public function search(Request $request)
    {
        $keyword = trim((string)$request->query('keyword', ''));

        // Nếu không có keyword: trả về nhà hàng nổi bật
        if ($keyword === '') {
            $results = Restaurant::orderByDesc('star_rating')
                ->limit(50)
                ->get();

            $results = $results->map(function ($r) {
                if ($r->image_url && !preg_match('/^https?:\/\//', $r->image_url)) {
                    $r->image_url = asset(trim($r->image_url, '/'));
                }
                return $r;
            });

            return response()->json($results);
        }

        // simple case-insensitive search on `name` with prefix-priority
        $clean = preg_replace('/[^\p{L}0-9\s]/u', '', mb_strtolower($keyword));
        $containsPattern = "%{$clean}%";
        $prefixPattern = "{$clean}%";

        // Return rows where name contains the keyword, but order prefix matches first, then by star_rating
        $restaurants = Restaurant::whereRaw('LOWER(name) LIKE ?', [$containsPattern])
            ->orderByRaw("CASE WHEN LOWER(name) LIKE ? THEN 0 ELSE 1 END", [$prefixPattern])
            ->orderByDesc('star_rating')
            ->limit(100)
            ->get()
            ->map(function ($r) {
                if ($r->image_url && !preg_match('/^https?:\/\//', $r->image_url)) {
                    $r->image_url = asset(trim($r->image_url, '/'));
                }
                return $r;
            });

        return response()->json($restaurants);
    }
    public function topRestaurants()
    {
        $restaurants = Restaurant::orderBy('star_rating', 'desc')
            ->take(10)
            ->get();

        return response()->json($restaurants);
    }
}
