<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 📋 Danh sách user có phân trang 10 user / trang
    public function index()
    {
        $users = User::paginate(10);

        // Nếu có ảnh, thêm domain
        $users->setCollection(
            $users->getCollection()->map(function ($user) {
                if ($user->image_url && !preg_match('/^https?:\/\//', $user->image_url)) {
                    $user->image_url = asset(trim($user->image_url, '/'));
                }
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
        ]);

        // Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/users'), $fileName);
            $validated['image_url'] = 'uploads/users/' . $fileName;
        }

        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']); // bỏ password gốc

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
        ]);

        // Upload ảnh mới nếu có
        if ($request->hasFile('image')) {
            if ($user->image_url && file_exists(public_path($user->image_url))) {
                unlink(public_path($user->image_url));
            }
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/users'), $fileName);
            $validated['image_url'] = 'uploads/users/' . $fileName;
        }

        // Hash password nếu có thay đổi
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
