<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private $bad_words = ['xấu', 'tệ', 'dở', 'ghét', 'bẩn'];

    private function censor($text)
    {
        foreach ($this->bad_words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $text = preg_replace($pattern, '***', $text);
        }
        return $text;
    }

    // GET /reviews hoặc /reviews/{restaurantId}
    public function index(Request $request, $restaurantId = null)
    {
        $query = Review::with(['user:user_id,username,image_url', 'restaurant:restaurant_id,name']);

        // lọc theo nhà hàng (frontend)
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        // lọc theo user hoặc keyword (admin)
        if ($request->has('user_id')) $query->where('user_id', $request->user_id);
        if ($request->has('keyword')) $query->where('comment', 'like', "%{$request->keyword}%");

        // phân trang
        $perPage = $request->get('per_page', 10); // default 10
        $reviews = $query->orderBy('review_id', 'DESC')->paginate($perPage);

        // chỉnh dữ liệu trả về
        $reviews->getCollection()->transform(function ($review) {
            return [
                'review_id' => $review->review_id,
                'restaurant_id' => $review->restaurant_id,
                'restaurant_name' => $review->restaurant->name ?? 'Nhà hàng',
                'user_id' => $review->user_id,
                'user_name' => $review->user->username ?? 'Người dùng',
                'avatar' => $review->user && $review->user->image_url
                    ? asset($review->user->image_url)
                    : asset('img/default-avatar.png'),
                'star_rating' => $review->star_rating,
                'comment' => $this->censor($review->comment),
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];
        });

        return response()->json($reviews);
    }
    // POST /reviews
    public function store(Request $request)
    {
        $request->validate([
            'restaurant_id' => 'required|integer',
            'user_id' => 'required|integer',
            'star_rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255'
        ]);

        $comment = $this->censor($request->comment ?? '');

        $review = Review::create([
            'restaurant_id' => $request->restaurant_id,
            'user_id' => $request->user_id,
            'star_rating' => $request->star_rating,
            'comment' => $comment
        ]);

        $review->load(['user:user_id,username,image_url', 'restaurant:restaurant_id,name']);

        return response()->json(['status' => true, 'data' => $this->transformReview($review)]);
    }

    // PUT /reviews/{id}
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $comment = $this->censor($request->comment ?? '');

        $review->update([
            'star_rating' => $request->star_rating,
            'comment' => $comment
        ]);

        $review->load(['user:user_id,username,image_url', 'restaurant:restaurant_id,name']);

        return response()->json(['status' => true, 'data' => $this->transformReview($review)]);
    }

    // DELETE /reviews/{id}
    public function destroy($id)
    {
        // Kiểm tra xem bản ghi còn tồn tại không
        $exists = Review::where('review_id', $id)->exists();

        // Nếu không tồn tại => đã bị xóa ở tab khác
        if (!$exists) {
            return response()->json([
                'status'  => false,
                'message' => 'Xóa không hợp lệ! Đánh giá đã bị xóa trước đó.'
            ], 404);
        }

        // Giữ nguyên logic xóa của bạn
        Review::where('review_id', $id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa đánh giá thành công!'
        ]);
    }
    // Chuyển đổi dữ liệu review
    private function transformReview($review)
    {
        return [
            'review_id' => $review->review_id,
            'restaurant_id' => $review->restaurant_id,
            'restaurant_name' => $review->restaurant->name ?? 'Nhà hàng',
            'user_id' => $review->user_id,
            'user_name' => $review->user->username ?? 'Người dùng',
            'avatar' => $review->user && $review->user->image_url
                ? asset($review->user->image_url)
                : asset('img/default-avatar.png'),
            'star_rating' => $review->star_rating,
            'comment' => $this->censor($review->comment),
            'created_at' => $review->created_at,
            'updated_at' => $review->updated_at,
        ];
    }
}
