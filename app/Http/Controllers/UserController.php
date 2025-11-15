<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng!'], 401);
        }


        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    // 📋 Danh sách user (có tìm kiếm + phân trang)
    public function index(Request $request)
    {
        $query = User::query();

        // Tìm kiếm theo username hoặc email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

        // Xử lý ảnh + đảm bảo phone, address luôn có dữ liệu
        $users->setCollection(
            $users->getCollection()->map(function ($user) {
                if ($user->image_url && !preg_match('/^https?:\/\//', $user->image_url)) {
                    $user->image_url = asset(trim($user->image_url, '/'));
                }
                $user->phone = $user->phone ?? '';
                $user->address = $user->address ?? '';
                return $user;
            })
        );

        return response()->json($users);
    }

    // ➕ Thêm user mới
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff,customer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        // Xử lý file ảnh nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/users'), $fileName);
            $validated['image_url'] = 'uploads/users/' . $fileName;
        }

        // Hash password
        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']);

        $user = User::create($validated);

        if ($user->image_url) {
            $user->image_url = asset($user->image_url);
        }

        return response()->json($user, 201);
    }

    // ✏️ Cập nhật user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|required|unique:users,username,' . $id . ',user_id|max:50',
            'email' => 'sometimes|required|email|unique:users,email,' . $id . ',user_id',
            'password' => 'nullable|min:6',
            'role' => 'sometimes|required|in:admin,staff,customer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        // Xử lý ảnh
        if ($request->hasFile('image')) {
            if ($user->image_url && file_exists(public_path($user->image_url))) {
                unlink(public_path($user->image_url));
            }
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/users'), $fileName);
            $validated['image_url'] = 'uploads/users/' . $fileName;
        }

        // Hash password nếu có
        if (!empty($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        if ($user->image_url) {
            $user->image_url = asset($user->image_url);
        }

        return response()->json($user);
    }

    // 🔍 Lấy chi tiết user
    public function show($id)
    {
        $user = User::findOrFail($id);
        if ($user->image_url && !preg_match('/^https?:\/\//', $user->image_url)) {
            $user->image_url = asset($user->image_url);
        }
        $user->phone = $user->phone ?? '';
        $user->address = $user->address ?? '';
        return response()->json($user);
    }

    // 🗑️ Xóa user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->image_url && file_exists(public_path($user->image_url))) {
            unlink(public_path($user->image_url));
        }

        $user->delete();

        return response()->json(['message' => 'User đã được xóa'], 200);
    }
}
