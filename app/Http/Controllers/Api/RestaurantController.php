<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;

class RestaurantController extends Controller
{
    public function topRestaurants()
    {
        $restaurants = Restaurant::orderByDesc('star_rating')
            ->orderByDesc('review_count')
            ->take(10)
            ->get();

        return response()->json($restaurants);
    }
}
