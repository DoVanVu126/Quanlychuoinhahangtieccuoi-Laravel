<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    // Lấy danh sách dịch vụ
    public function index()
    {
        $services = Service::with('restaurant')->paginate(10);
    return response()->json($services);
    }

    // Lấy chi tiết 1 dịch vụ
    public function show($id)
    {
        $service = Service::where('service_id', $id)->with('restaurant')->first();
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        return response()->json($service);
    }

    // Thêm mới dịch vụ
    public function store(Request $request)
{
    // Validate dữ liệu gửi lên
   $validated = $request->validate([
    'restaurant_id' => 'required|integer',
    'name' => 'required|string|max:255|unique:services,name',
    'description' => 'nullable|string',
    'price' => 'required|numeric|min:0',
    'status' => 'sometimes|boolean', // optional
    'image_url' => 'nullable|string|max:255',
    'created_at' => 'nullable|date',
]);
    // Tạo dịch vụ mới
    $service = Service::create($validated);

    // Trả về response
    return response()->json($service, 201);
}


    // Cập nhật dịch vụ
    public function update(Request $request, $id)
{
    $service = Service::find($id);

    if (!$service) {
        return response()->json(['message' => 'Service not found'], 404);
    }

    // Validate dữ liệu
    $validated = $request->validate([
        'restaurant_id' => 'required|integer',
        'name' => 'required|string|max:255|unique:services,name,' . $id . ',service_id',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
        'status' => 'required|boolean', // bắt buộc có status
        'image_url' => 'nullable|string|max:255',
        'created_at' => 'nullable|date',
    ]);

    // Cập nhật
    $service->update($validated);

    return response()->json($service);
}

    // Xóa dịch vụ
    public function destroy($id)
    {
        $service = Service::where('service_id', $id)->first();
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $service->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
