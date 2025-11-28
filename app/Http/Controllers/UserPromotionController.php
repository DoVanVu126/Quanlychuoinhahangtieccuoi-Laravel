<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPromotion;
use App\Models\Notification;

class UserPromotionController extends Controller
{
    // Lưu mã khuyến mãi của user
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'promotion_id' => 'required|exists:promotions,promotion_id',
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
        ]);

        // Kiểm tra đã lưu chưa
        $exists = UserPromotion::where([
            'user_id' => $request->user_id,
            'promotion_id' => $request->promotion_id,
            'restaurant_id' => $request->restaurant_id,
        ])->first();

        if ($exists) {
            return response()->json(['message' => 'Mã đã lưu trước đó'], 200);
        }

        // Lưu user promotion
        $userPromotion = UserPromotion::create([
            'user_id' => $request->user_id,
            'promotion_id' => $request->promotion_id,
            'restaurant_id' => $request->restaurant_id,
            'used' => false,
        ]);

        // ➤ TẠO THÔNG BÁO
        Notification::create([
            'user_id' => $request->user_id,
            'promotion_id' => $request->promotion_id,
            'title' => 'Lưu khuyến mãi thành công',
            'message' => 'Bạn đã lưu một mã khuyến mãi mới.',
            'type' => 'success',
            'is_read' => false,
        ]);

        return response()->json(['message' => 'Đã lưu mã khuyến mãi', 'data' => $userPromotion], 201);
    }

    // Lấy tất cả mã đã lưu của user
    public function index(Request $request)
    {
        $user_id = $request->query('user_id');

        $userPromos = UserPromotion::with('promotion')
            ->where('user_id', $user_id)
            ->get();

        return response()->json(
            $userPromos->map(function ($item) {
                return [
                    'user_promotion_id' => $item->id,
                    'promotion_id' => $item->promotion_id,
                    'title' => $item->promotion->title,
                    'description' => $item->promotion->description,
                    'promotion_code' => $item->promotion->promotion_code,
                    'discount_type' => $item->promotion->discount_type,
                    'discount_value' => $item->promotion->discount_value,
                    'start_date' => $item->promotion->start_date,
                    'end_date' => $item->promotion->end_date,
                    'image' => $item->promotion->image,
                    'status' => $item->promotion->status,
                    'restaurant_id' => $item->restaurant_id
                ];
            })
        );
    }

    public function destroy($id)
    {
        $userPromo = UserPromotion::find($id);

        if (!$userPromo) {
            return response()->json(['message' => 'Mã không tồn tại'], 404);
        }

        $userPromo->delete();

        return response()->json(['message' => 'Đã xóa mã thành công']);
    }
}
