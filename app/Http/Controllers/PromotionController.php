<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    // 📌 Lấy danh sách khuyến mãi
    public function index(Request $request)
    {
        $query = Promotion::with('restaurant');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('promotion_code', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%");
            });
        }

        $promotions = $query->orderBy('promotion_id', 'asc')->paginate(10);

        // Chuẩn hóa URL ảnh
        $promotions->getCollection()->transform(function ($promo) {
            if ($promo->image_url && !preg_match('/^https?:\/\//', $promo->image_url)) {
                $promo->image_url = asset($promo->image_url);
            }
            return $promo;
        });

        return response()->json($promotions);
    }

    // 📌 API user (không phân trang)
    public function getAll()
    {
        $promotions = Promotion::orderBy('promotion_id', 'desc')->get();

        $promotions->transform(function ($promo) {
            if ($promo->image) {
                $promo->image = preg_match('/^https?:\/\//', $promo->image)
                    ? $promo->image
                    : asset($promo->image);
            } else {
                $promo->image = asset('img/default.jpg');
            }
            return $promo;
        });

        return response()->json(['data' => $promotions]);
    }

    // 📌 Check mã khuyến mãi
    public function checkCode(Request $request)
    {
        $code = $request->code;

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập mã khuyến mãi'
            ], 400);
        }

        $promo = Promotion::where('promotion_code', $code)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mã hợp lệ',
            'data' => $promo
        ]);
    }

    // 📌 Xem chi tiết
    public function show($id)
    {
        $promo = Promotion::with('restaurant')->find($id);

        if (!$promo) {
            return response()->json([
                'message' => 'Không tìm thấy khuyến mãi'
            ], 404);
        }

        if ($promo->image && !preg_match('/^https?:\/\//', $promo->image)) {
            $promo->image_url = asset($promo->image);
        }

        return response()->json($promo);
    }

    // 📌 Thêm khuyến mãi
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'restaurant_id'   => 'required|integer',
                'promotion_code'  => 'required|max:50',
                'title'           => 'required|max:100',
                'description'     => 'nullable|max:255',
                'discount_type'   => 'required|in:percent,amount',
                'discount_value'  => 'required|numeric',
                'start_date'      => 'required|date',
                'end_date'        => 'required|date|after_or_equal:start_date',
                'status'          => 'nullable|in:active,expired,upcoming',
                'image'           => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048'
            ]);

            // Check trùng code
            $exists = Promotion::where('promotion_code', $validated['promotion_code'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Mã khuyến mãi đã tồn tại'
                ], 409);
            }

            // Upload ảnh
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/promotions'), $fileName);
                $validated['image'] = 'uploads/promotions/' . $fileName;
            }

            $promo = Promotion::create($validated);

            if ($promo->image) {
                $promo->image = asset($promo->image);
            }

            return response()->json([
                'message' => 'Thêm khuyến mãi thành công',
                'data' => $promo
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi khi thêm khuyến mãi',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 📌 Cập nhật
    public function update(Request $request, $id)
    {
        try {
            $promo = Promotion::find($id);

            if (!$promo) {
                return response()->json([
                    'message' => 'Không tìm thấy khuyến mãi'
                ], 404);
            }

            // Check stale data
            if ($request->has('updated_at') && $promo->updated_at != $request->updated_at) {
                return response()->json([
                    'message' => 'Dữ liệu đã bị thay đổi, vui lòng tải lại'
                ], 409);
            }

            $validated = $request->validate([
                'restaurant_id'  => 'sometimes|required|integer',
                'promotion_code' => 'sometimes|required|max:50',
                'title'          => 'sometimes|required|max:100',
                'description'    => 'nullable|max:255',
                'discount_type'  => 'sometimes|required|in:percent,amount',
                'discount_value' => 'sometimes|required|numeric',
                'start_date'     => 'sometimes|required|date',
                'end_date'       => 'sometimes|required|date|after_or_equal:start_date',
                'status'         => 'nullable|in:active,expired,upcoming',
                'image'          => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            // Check trùng code
            if (isset($validated['promotion_code'])) {
                $exists = Promotion::where('promotion_code', $validated['promotion_code'])
                    ->where('promotion_id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'Mã khuyến mãi đã tồn tại'
                    ], 409);
                }
            }

            // Upload ảnh mới
            if ($request->hasFile('image')) {
                if ($promo->image && file_exists(public_path($promo->image))) {
                    unlink(public_path($promo->image));
                }

                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/promotions'), $fileName);
                $validated['image'] = 'uploads/promotions/' . $fileName;
            }

            $promo->update($validated);

            if ($promo->image) {
                $promo->image = asset($promo->image);
            }

            return response()->json([
                'message' => 'Cập nhật khuyến mãi thành công',
                'data' => $promo
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi khi cập nhật khuyến mãi',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // 📌 Xóa
    public function destroy($id)
    {
        $promo = Promotion::find($id);

        if (!$promo) {
            return response()->json([
                'message' => 'Khuyến mãi không tồn tại hoặc đã bị xóa'
            ], 404);
        }

        if ($promo->image && file_exists(public_path($promo->image))) {
            unlink(public_path($promo->image));
        }

        $promo->delete();

        return response()->json([
            'message' => 'Xóa khuyến mãi thành công'
        ]);
    }

    // 📌 Recommend
    public function recommend(Request $request)
    {
        $userId = $request->query('user_id');
        if (!$userId) {
            return response()->json(['message' => 'user_id is required'], 400);
        }

        $bookings = DB::table('bookings')
            ->where('created_by_user_id', $userId)
            ->get();

        if ($bookings->isEmpty()) {
            $promos = Promotion::orderBy('promotion_id', 'desc')->take(10)->get();
            return response()->json(['data' => $promos]);
        }

        $topRestaurant = $bookings->groupBy('restaurant_id')
            ->sortByDesc(fn($group) => $group->count())
            ->keys()
            ->first();

        $topTheme = $bookings->groupBy('event_type')
            ->sortByDesc(fn($group) => $group->count())
            ->keys()
            ->first();

        $promos = Promotion::where(function ($q) use ($topRestaurant, $topTheme) {
            if ($topRestaurant) $q->orWhere('restaurant_id', $topRestaurant);
            if ($topTheme) {
                $q->orWhere('title', 'LIKE', "%{$topTheme}%")
                  ->orWhere('description', 'LIKE', "%{$topTheme}%");
            }
        })
        ->orderBy('promotion_id', 'desc')
        ->take(10)
        ->get();

        if ($promos->isEmpty()) {
            $promos = Promotion::orderBy('promotion_id', 'desc')->take(10)->get();
        }

        return response()->json(['data' => $promos]);
    }
}
