<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    public function index()
    {
        $foods = Food::with(['restaurant', 'foodType'])
        ->select('food_id', 'food_type_id', 'restaurant_id', 'name', 'description', 'unit', 'image_url')
        ->paginate(10);

    return response()->json($foods);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $food = Food::create($request->all());
        return response()->json($food, 201);
    }

    public function show($id)
    {
        $food = Food::with(['restaurant', 'foodType'])->find($id);
        if (!$food) {
            return response()->json(['message' => 'Không tìm thấy món ăn'], 404);
        }
        return response()->json($food);
    }

    public function update(Request $request, $id)
    {
        $food = Food::find($id);
        if (!$food) {
            return response()->json(['message' => 'Không tìm thấy món ăn'], 404);
        }

        $validator = Validator::make($request->all(), [
            'food_type_id' => 'required|exists:food_types,food_type_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $food->update($request->all());
        return response()->json($food);
    }

    public function destroy($id)
    {
        $food = Food::find($id);
        if (!$food) {
            return response()->json(['message' => 'Không tìm thấy món ăn'], 404);
        }

        $food->delete();
        return response()->json(['message' => 'Xóa món ăn thành công']);
    }
}
