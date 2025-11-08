<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    // 📌 Lấy danh sách dịch vụ
    public function index()
    {
        $services = Service::with('restaurant')->paginate(10);

        // Chuẩn hóa URL ảnh (thêm domain nếu cần)
        $services->setCollection(
            $services->getCollection()->map(function ($service) {
                if ($service->image_url && !preg_match('/^https?:\/\//', $service->image_url)) {
                    // Đảm bảo không có dấu '/' dư
                    $service->image_url = asset(trim($service->image_url, '/'));
                }
                return $service;
            })
        );

        return response()->json($services);
    }

    // 📌 Lấy chi tiết 1 dịch vụ
    public function show($id)
    {
        $service = Service::with('restaurant')
            ->where('service_id', $id)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        if ($service->image_url && !preg_match('/^https?:\/\//', $service->image_url)) {
            $service->image_url = asset(trim($service->image_url, '/'));
        }

        return response()->json($service);
    }

    // 📌 Thêm mới dịch vụ
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:255|unique:services,name',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Nếu có upload ảnh
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/services'), $fileName);

            // Lưu đường dẫn tương đối (không có dấu '/')
            $validated['image_url'] = 'uploads/services/' . $fileName;
        }

        $service = Service::create($validated);

        // Trả về ảnh có domain đầy đủ
        if ($service->image_url) {
            $service->image_url = asset($service->image_url);
        }

        return response()->json($service, 201);
    }

    // 📌 Cập nhật dịch vụ
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|integer',
            'name' => 'required|string|max:255|unique:services,name,' . $id . ',service_id',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:available,unavailable,maintenance',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        // Nếu có upload ảnh mới → xóa ảnh cũ + lưu mới
        if ($request->hasFile('image')) {
            if ($service->image_url && file_exists(public_path($service->image_url))) {
                unlink(public_path($service->image_url));
            }

            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/services'), $fileName);
            $validated['image_url'] = 'uploads/services/' . $fileName;
        }

        $service->update($validated);

        if ($service->image_url) {
            $service->image_url = asset($service->image_url);
        }

        return response()->json($service);
    }

    // 📌 Xóa dịch vụ
    public function destroy($id)
    {
        $service = Service::where('service_id', $id)->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        // Xóa ảnh trong thư mục (nếu có)
        if ($service->image_url && file_exists(public_path($service->image_url))) {
            unlink(public_path($service->image_url));
        }

        $service->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
