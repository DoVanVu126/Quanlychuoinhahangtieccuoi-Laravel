<?php

namespace App\Http\Controllers;

use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HallController extends Controller
{
    // 📋 Danh sách sảnh có phân trang (10 sảnh / trang)
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $search = $request->query('search', null);

        $query = Hall::with('restaurant');

        if ($search) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $halls = $query->paginate(10, ['*'], 'page', $page);

        // Thêm domain vào đường dẫn ảnh (nếu có)
        $halls->setCollection(
            $halls->getCollection()->map(function ($hall) {
                if ($hall->image_url && !preg_match('/^https?:\/\//', $hall->image_url)) {
                    $hall->image_url = asset(trim($hall->image_url, '/'));
                } else if (!$hall->image_url) {
                    $hall->image_url = asset('images/default-service.png');
                }
                return $hall;
            })
        );

        return response()->json($halls);
    }

    // 📋 Chi tiết 1 sảnh
    public function show($id)
    {
        $hall = Hall::with('restaurant')->where('hall_id', $id)->first();

        if (!$hall) {
            return response()->json(['message' => 'Hall not found'], 404);
        }

        if ($hall->image_url && !preg_match('/^https?:\/\//', $hall->image_url)) {
            $hall->image_url = asset(trim($hall->image_url, '/'));
        } else if (!$hall->image_url) {
            $hall->image_url = asset('images/default-service.png');
        }

        return response()->json($hall);
    }

    // ➕ Thêm mới sảnh
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:100|unique:halls,name',
            'capacity' => 'nullable|integer',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        // Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $validated['image_url'] = $this->handleImageUpload($request->file('image'), 'uploads/halls');
        }

        $hall = Hall::create($validated);

        if ($hall->image_url) $hall->image_url = asset($hall->image_url);
        else $hall->image_url = asset('images/default-service.png');

        return response()->json($hall, 201);
    }

    // ✏️ Cập nhật sảnh
    public function update(Request $request, $id)
    {
        $hall = Hall::where('hall_id', $id)->first();

        if (!$hall) return response()->json(['message' => 'Hall not found'], 404);

        $validated = $request->validate([
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:100|unique:halls,name,' . $id . ',hall_id',
            'capacity' => 'nullable|integer',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        // Nếu có ảnh mới
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ
            if ($hall->image_url && file_exists(public_path($hall->image_url))) {
                @unlink(public_path($hall->image_url));
            }
            $validated['image_url'] = $this->handleImageUpload($request->file('image'), 'uploads/halls');
        }

        $hall->update($validated);

        if ($hall->image_url) $hall->image_url = asset($hall->image_url);
        else $hall->image_url = asset('images/default-service.png');

        return response()->json($hall);
    }

    // 🗑️ Xóa sảnh
    public function destroy($id)
    {
        $hall = Hall::where('hall_id', $id)->first();

        if (!$hall) return response()->json(['message' => 'Hall not found'], 404);

        if ($hall->image_url && file_exists(public_path($hall->image_url))) {
            @unlink(public_path($hall->image_url));
        }

        $hall->delete();

        return response()->json(['message' => 'Đã xóa sảnh thành công']);
    }

    // 🔹 Lấy sảnh theo nhà hàng
    public function getHallsByRestaurant($restaurant_id)
    {
        $halls = DB::table('halls')->where('restaurant_id', $restaurant_id)->get();
        return response()->json($halls);
    }

    // 🔹 Hàm xử lý upload ảnh, tạo folder nếu chưa có
    private function handleImageUpload($file, $folder)
    {
        $uploadPath = public_path($folder);
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($uploadPath, $fileName);

        return $folder . '/' . $fileName;
    }
}
