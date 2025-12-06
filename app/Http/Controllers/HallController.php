<?php

namespace App\Http\Controllers;

use App\Models\Hall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HallController extends Controller
{
    // 📋 Danh sách sảnh
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $search = $request->query('search');

        $query = Hall::with('restaurant');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $halls = $query->paginate(10, ['*'], 'page', $page);

        $halls->setCollection(
            $halls->getCollection()->map(function ($hall) {
                if ($hall->image_url && !preg_match('/^https?:\/\//', $hall->image_url)) {
                    $hall->image_url = asset(trim($hall->image_url, '/'));
                } elseif (!$hall->image_url) {
                    $hall->image_url = asset('images/default-service.png');
                }
                return $hall;
            })
        );

        return response()->json($halls);
    }

    // 📋 Chi tiết
    public function show($id)
    {
        $hall = Hall::with('restaurant')->where('hall_id', $id)->first();
        if (!$hall) {
            return response()->json(['message' => 'Không tìm thấy sảnh'], 404);
        }

        if ($hall->image_url && !preg_match('/^https?:\/\//', $hall->image_url)) {
            $hall->image_url = asset(trim($hall->image_url, '/'));
        } elseif (!$hall->image_url) {
            $hall->image_url = asset('images/default-service.png');
        }

        return response()->json($hall);
    }

    // ➕ Thêm sảnh
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|integer',
            'name'          => 'required|string|max:100|unique:halls,name',
            'capacity'      => 'nullable|integer|min:1',
            'price'         => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:500',
            'status'        => 'required|in:available,unavailable,maintenance',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ], [
            'restaurant_id.required' => 'Vui lòng chọn nhà hàng',
            'name.required'          => 'Tên sảnh không được để trống',
            'name.unique'            => 'Tên sảnh đã tồn tại',
            'capacity.integer'       => 'Sức chứa phải là số nguyên',
            'price.numeric'          => 'Giá phải là số',
            'status.in'              => 'Trạng thái không hợp lệ',
            'image.image'            => 'File phải là hình ảnh',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Upload ảnh
        $data = $validator->validated();
        if ($request->hasFile('image')) {
            $data['image_url'] = $this->handleImageUpload($request->file('image'), 'uploads/halls');
        }

        $hall = Hall::create($data);

        $hall->image_url = $hall->image_url
            ? asset($hall->image_url)
            : asset('images/default-service.png');

        return response()->json([
            'message' => 'Thêm sảnh thành công',
            'data'    => $hall
        ], 201);
    }

    // ✏️ Cập nhật
    public function update(Request $request, $id)
    {
        $hall = Hall::where('hall_id', $id)->first();
        if (!$hall) {
            return response()->json(['message' => 'Không tìm thấy sảnh'], 404);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|integer',
            'name'          => 'required|string|max:100|unique:halls,name,' . $id . ',hall_id',
            'capacity'      => 'nullable|integer|min:1',
            'price'         => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:500',
            'status'        => 'required|in:available,unavailable,maintenance',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Xử lý ảnh mới
        if ($request->hasFile('image')) {
            if ($hall->image_url && file_exists(public_path($hall->image_url))) {
                @unlink(public_path($hall->image_url));
            }
            $data['image_url'] = $this->handleImageUpload($request->file('image'), 'uploads/halls');
        }

        $hall->update($data);

        $hall->image_url = $hall->image_url
            ? asset($hall->image_url)
            : asset('images/default-service.png');

        return response()->json([
            'message' => 'Cập nhật sảnh thành công',
            'data'    => $hall
        ]);
    }

    // 🗑️ Xóa
    public function destroy($id)
    {
        $hall = Hall::where('hall_id', $id)->first();
        if (!$hall) {
            return response()->json(['message' => 'Không tìm thấy sảnh'], 404);
        }

        if ($hall->image_url && file_exists(public_path($hall->image_url))) {
            @unlink(public_path($hall->image_url));
        }

        $hall->delete();

        return response()->json([
            'message' => 'Đã xóa sảnh thành công'
        ]);
    }

    // 🔹 Lấy sảnh theo nhà hàng
    public function getHallsByRestaurant($restaurant_id)
    {
        $halls = DB::table('halls')
            ->where('restaurant_id', $restaurant_id)
            ->get();

        return response()->json($halls);
    }

    // 🔹 Upload hình
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
