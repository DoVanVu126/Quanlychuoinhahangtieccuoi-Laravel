<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    // 📌 Lấy danh sách món ăn
    public function index()
    {
        $foods = Food::with(['restaurant', 'foodType'])
            ->paginate(10);

        // Chuẩn hóa URL ảnh
        $foods->setCollection(
            $foods->getCollection()->map(function ($food) {
                if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
                    $food->image_url = asset(trim($food->image_url, '/'));
                }
                return $food;
            })
        );

        return response()->json($foods);
    }

    // 📌 Lấy chi tiết món ăn
    public function show($id)
    {
        $food = Food::with(['restaurant', 'foodType'])->find($id);

        if (!$food) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
            $food->image_url = asset(trim($food->image_url, '/'));
        }

        return response()->json($food);
    }

    // 📌 Thêm món ăn
    public function store(Request $request)
    {
        $validated = $request->validate([
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150|unique:foods,name',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/foods'), $fileName);
            $validated['image_url'] = 'uploads/foods/' . $fileName;
        }

        $food = Food::create($validated);

        // Chuẩn hóa URL ảnh
        if ($food->image_url) {
            $food->image_url = asset($food->image_url);
        }

        return response()->json($food, 201);
    }

    // 📌 Cập nhật món ăn
    public function update(Request $request, $id)
    {
        $food = Food::find($id);
        if (!$food) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        $validated = $request->validate([
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150|unique:foods,name,' . $id . ',food_id',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Upload ảnh mới nếu có → xóa ảnh cũ
        if ($request->hasFile('image')) {
            if ($food->image_url && file_exists(public_path($food->image_url))) {
                unlink(public_path($food->image_url));
            }

            $file = $request->file('image');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('uploads/foods'), $fileName);
            $validated['image_url'] = 'uploads/foods/' . $fileName;
        }

        $food->update($validated);

        if ($food->image_url) {
            $food->image_url = asset($food->image_url);
        }

        return response()->json($food);
    }

    // 📌 Xóa món ăn
    public function destroy($id)
    {
        $food = Food::find($id);
        if (!$food) {
            return response()->json(['message' => 'Món ăn không tồn tại'], 404);
        }

        // Xóa ảnh nếu có
        if ($food->image_url && file_exists(public_path($food->image_url))) {
            unlink(public_path($food->image_url));
        }

        $food->delete();

        return response()->json(['message' => 'Xóa món ăn thành công']);
    }

    // 📌 Lấy món ăn theo nhà hàng
    public function getFoodsByRestaurant($restaurant_id)
    {
        $foods = Food::with(['foodType', 'restaurant'])
            ->where('restaurant_id', $restaurant_id)
            ->get();

        // Chuẩn hóa URL ảnh
        $foods = $foods->map(function ($food) {
            if ($food->image_url && !preg_match('/^https?:\/\//', $food->image_url)) {
                $food->image_url = asset(trim($food->image_url, '/'));
            }
            return $food;
        });

        return response()->json($foods);
    }
}
