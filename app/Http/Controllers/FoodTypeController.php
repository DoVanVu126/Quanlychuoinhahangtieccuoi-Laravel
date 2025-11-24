<?php

namespace App\Http\Controllers;

use App\Models\FoodType;
use Illuminate\Http\Request;

class FoodTypeController extends Controller
{
    public function index()
    {
        $foodTypes = FoodType::all(['food_type_id', 'name']); // chỉ lấy ID và tên
        return response()->json($foodTypes);
    }
}
