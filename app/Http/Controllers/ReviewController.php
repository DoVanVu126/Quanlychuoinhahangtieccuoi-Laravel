<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Lấy danh sách review của 1 nhà hàng
    public function index($restaurantId)
    {
        $reviews = Review::where('restaurant_id', $restaurantId)
            ->with('user:user_id,username,image_url') // lấy username + avatar
            ->orderBy('review_id', 'DESC')
            ->get()
            ->map(function ($review) {
                return [
                    'review_id' => $review->review_id,
                    'restaurant_id' => $review->restaurant_id,
                    'user_id' => $review->user_id,
                    'user_name' => $review->user->username ?? 'Người dùng',
                    // avatar trả về URL đầy đủ
                    'avatar' => $review->user && $review->user->image_url
                        ? asset($review->user->image_url)
                        : asset('img/default-avatar.png'),
                    'star_rating' => $review->star_rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $reviews
        ]);
    }

    // Thêm đánh giá
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_id' => 'required|integer',
            'user_id' => 'required|integer',
            'star_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255'
        ]);

        $review = Review::create([
            'restaurant_id' => $request->restaurant_id,
            'user_id' => $request->user_id,
            'star_rating' => $request->star_rating,
            'comment' => $request->comment
        ]);

        $review->load('user:user_id,username,image_url');

        return response()->json([
            'status' => true,
            'message' => 'Đánh giá thành công!',
            'data' => [
                'review_id' => $review->review_id,
                'restaurant_id' => $review->restaurant_id,
                'user_id' => $review->user_id,
                'user_name' => $review->user->username ?? 'Người dùng',
                'avatar' => $review->user && $review->user->image_url
                    ? asset($review->user->image_url)
                    : asset('img/default-avatar.png'),
                'star_rating' => $review->star_rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ]
        ]);
    }

    // Sửa đánh giá
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $review->update([
            'star_rating' => $request->star_rating,
            'comment' => $request->comment
        ]);

        $review->load('user:user_id,username,image_url');

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật đánh giá thành công!',
            'data' => [
                'review_id' => $review->review_id,
                'restaurant_id' => $review->restaurant_id,
                'user_id' => $review->user_id,
                'user_name' => $review->user->username ?? 'Người dùng',
                'avatar' => $review->user && $review->user->image_url
                    ? asset($review->user->image_url)
                    : asset('img/default-avatar.png'),
                'star_rating' => $review->star_rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ]
        ]);
    }

    // Xóa đánh giá
    public function destroy($id)
    {
        Review::where('review_id', $id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa đánh giá thành công!'
        ]);
    }
}
