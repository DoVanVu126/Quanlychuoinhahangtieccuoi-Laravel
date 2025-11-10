<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Inventory::with('restaurant');

        // Filter theo restaurant_id
        if ($request->has('restaurant_id')) {
            $query->byRestaurant($request->restaurant_id);
        }

        // Filter sản phẩm cần đặt hàng lại
        if ($request->has('needs_reorder') && $request->needs_reorder) {
            $query->needsReorder();
        }

        // Search theo tên sản phẩm
        if ($request->has('search')) {
            $query->where('item_name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $inventories = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inventories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $restaurants = Restaurant::all();
        return response()->json([
            'success' => true,
            'restaurants' => $restaurants
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required|exists:restaurants,restaurant_id',
            'item_name' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'status' => 'nullable|in:available,expired,near_expiry',
        ], [
            'restaurant_id.required' => 'Vui lòng chọn nhà hàng',
            'restaurant_id.exists' => 'Nhà hàng không tồn tại',
            'item_name.required' => 'Vui lòng nhập tên sản phẩm',
            'unit.required' => 'Vui lòng nhập đơn vị tính',
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 0',
            'reorder_level.required' => 'Vui lòng nhập mức đặt hàng lại',
            'reorder_level.min' => 'Mức đặt hàng lại phải lớn hơn hoặc bằng 0',
            'expiry_date.after' => 'Hạn sử dụng phải sau ngày hôm nay',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $inventory = Inventory::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Thêm sản phẩm vào kho thành công',
            'data' => $inventory
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $inventory = Inventory::with('restaurant')->find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $inventory = Inventory::with('restaurant')->find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm'
            ], 404);
        }

        $restaurants = Restaurant::all();

        return response()->json([
            'success' => true,
            'data' => $inventory,
            'restaurants' => $restaurants
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'sometimes|required|exists:restaurants,restaurant_id',
            'item_name' => 'sometimes|required|string|max:100',
            'unit' => 'sometimes|required|string|max:50',
            'quantity' => 'sometimes|required|numeric|min:0',
            'reorder_level' => 'sometimes|required|numeric|min:0',
        ], [
            'restaurant_id.exists' => 'Nhà hàng không tồn tại',
            'item_name.required' => 'Vui lòng nhập tên sản phẩm',
            'unit.required' => 'Vui lòng nhập đơn vị tính',
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.min' => 'Số lượng phải lớn hơn hoặc bằng 0',
            'reorder_level.required' => 'Vui lòng nhập mức đặt hàng lại',
            'reorder_level.min' => 'Mức đặt hàng lại phải lớn hơn hoặc bằng 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $inventory->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật sản phẩm thành công',
            'data' => $inventory
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm'
            ], 404);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa sản phẩm thành công'
        ]);
    }

    /**
     * Lấy danh sách sản phẩm cần đặt hàng lại
     */
    public function lowStock(Request $request)
    {
        $query = Inventory::with('restaurant')->needsReorder();

        if ($request->has('restaurant_id')) {
            $query->byRestaurant($request->restaurant_id);
        }

        $inventories = $query->orderBy('quantity', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $inventories,
            'count' => $inventories->count()
        ]);
    }

    /**
     * Cập nhật số lượng (thêm/bớt)
     */
    public function updateQuantity(Request $request, $id)
    {
        $inventory = Inventory::find($id);

        if (!$inventory) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy sản phẩm'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:import,export',
            'quantity' => 'required|numeric|min:0.01',
        ], [
            'type.required' => 'Vui lòng chọn loại giao dịch',
            'type.in' => 'Loại giao dịch không hợp lệ',
            'quantity.required' => 'Vui lòng nhập số lượng',
            'quantity.min' => 'Số lượng phải lớn hơn 0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->type === 'import') {
            $inventory->quantity += $request->quantity;
        } else {
            if ($inventory->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng xuất kho vượt quá số lượng tồn kho'
                ], 422);
            }
            $inventory->quantity -= $request->quantity;
        }

        $inventory->save();

        return response()->json([
            'success' => true,
            'message' => $request->type === 'import' ? 'Nhập kho thành công' : 'Xuất kho thành công',
            'data' => $inventory
        ]);
    }

    /**
     * Lấy danh sách sản phẩm đã hết hạn
     */
    public function expired(Request $request)
    {
        $query = Inventory::with('restaurant')->expired();

        if ($request->has('restaurant_id')) {
            $query->byRestaurant($request->restaurant_id);
        }

        $inventories = $query->orderBy('expiry_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $inventories,
            'count' => $inventories->count()
        ]);
    }

    /**
     * Lấy danh sách sản phẩm sắp hết hạn
     */
    public function nearExpiry(Request $request)
    {
        $query = Inventory::with('restaurant')->nearExpiry();

        if ($request->has('restaurant_id')) {
            $query->byRestaurant($request->restaurant_id);
        }

        $inventories = $query->orderBy('expiry_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $inventories,
            'count' => $inventories->count()
        ]);
    }
}
