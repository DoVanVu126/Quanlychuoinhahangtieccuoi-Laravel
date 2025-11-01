<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Lấy danh sách user
    public function index()
    {
        return response()->json(User::all(), 200);
    }

    // Thêm user
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff,customer',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password_hash' => Hash::make($request->password),
            'email' => $request->email,
            'role' => $request->role,
            'image_url' => $request->image_url,
        ]);

        return response()->json($user, 201);
    }

    // Sửa user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'username' => $request->username ?? $user->username,
            'email' => $request->email ?? $user->email,
            'role' => $request->role ?? $user->role,
            'image_url' => $request->image_url ?? $user->image_url,
        ]);

        return response()->json($user, 200);
    }

    // Xóa user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted'], 200);
    }
}
