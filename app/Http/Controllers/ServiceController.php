<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    // Lấy danh sách dịch vụ
    public function index()
    {
        $services = Service::with('restaurant')->get();
        return response()->json($services);
    }

    // Lấy chi tiết 1 dịch vụ
    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        return response()->json($service);
    }

    // ✅ Thêm mới dịch vụ
    public function store(Request $request)
    {
        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);
        return response()->json($service, 201);
    }

    // ✅ Cập nhật dịch vụ
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $service->update($request->all());
        return response()->json($service);
    }

    // ✅ Xóa dịch vụ
    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        $service->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
