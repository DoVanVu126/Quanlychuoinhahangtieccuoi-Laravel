<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    // 📌 Danh sách dịch vụ với pagination và validate page
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        if (!is_numeric($page) || $page < 1) {
            return response()->json(['message' => 'Page không hợp lệ'], 400);
        }

        $search = $request->query('search', null);

        $query = Service::with('restaurant');

        if ($search) {
            // Dùng LIKE case-insensitive, bỏ dấu tiếng Việt nếu muốn
            $query->where('name', 'LIKE', '%' . $search . '%');
        }
        $services = $query->paginate(10);

        if ($page > $services->lastPage()) {
            return response()->json(['message' => 'Page không tồn tại'], 404);
        }

        $services->setCollection(
            $services->getCollection()->map(function ($service) {
                if ($service->image_url && !preg_match('/^https?:\/\//', $service->image_url)) {
                    $service->image_url = asset(trim($service->image_url, '/'));
                } else if (!$service->image_url) {
                    $service->image_url = asset('images/default-service.png');
                }
                return $service;
            })
        );

        return response()->json($services);
    }

    // 📌 Chi tiết dịch vụ
    public function show($id)
    {
        $service = Service::with('restaurant')->find($id);

        if (!$service) return response()->json(['message' => 'Service not found'], 404);

        if ($service->image_url && !preg_match('/^https?:\/\//', $service->image_url)) {
            $service->image_url = asset(trim($service->image_url, '/'));
        } else if (!$service->image_url) {
            $service->image_url = asset('images/default-service.png');
        }

        return response()->json($service);
    }

    // 📌 Thêm mới dịch vụ
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
        ], [
            'image.image' => 'Chỉ được upload hình ảnh',
            'image.mimes' => 'Chỉ chấp nhận định dạng jpg, jpeg, png, gif',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['restaurant_id', 'name', 'description', 'price', 'status']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/services'), $fileName);
            $data['image_url'] = 'uploads/services/' . $fileName;
        }

        $service = Service::create($data);

        if ($service->image_url) {
            $service->image_url = asset($service->image_url);
        } else {
            $service->image_url = asset('images/default-service.png');
        }

        return response()->json($service, 201);
    }

    // 📌 Update dịch vụ
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) return response()->json(['message' => 'Service not found'], 404);

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:255|unique:services,name,' . $id . ',service_id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:10240',
        ], [
            'image.image' => 'Chỉ được upload hình ảnh',
            'image.mimes' => 'Chỉ chấp nhận định dạng jpg, jpeg, png, gif',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $data = $request->only(['restaurant_id', 'name', 'description', 'price', 'status']);

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ
            if ($service->image_url && file_exists(public_path($service->image_url))) {
                unlink(public_path($service->image_url));
            }
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/services'), $fileName);
            $data['image_url'] = 'uploads/services/' . $fileName;
        } else {
            // Giữ nguyên ảnh cũ
            $data['image_url'] = $service->image_url;
        }

        $service->update($data);

        if ($service->image_url) $service->image_url = asset($service->image_url);
        else $service->image_url = asset('images/default-service.png');

        return response()->json($service);
    }

    // 📌 Xóa dịch vụ
    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) return response()->json(['message' => 'Service not found'], 404);

        if ($service->image_url && file_exists(public_path($service->image_url))) {
            unlink(public_path($service->image_url));
        }

        $service->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    // 📌 Lấy dịch vụ theo nhà hàng
    public function getServicesByRestaurant($id)
    {
        $services = Service::where('restaurant_id', $id)->get();
        return response()->json($services);
    }
}
