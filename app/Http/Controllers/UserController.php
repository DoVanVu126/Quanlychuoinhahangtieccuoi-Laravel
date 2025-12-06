<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // ==========================
    // 📋 Danh sách user (validate query)
    // ==========================
    public function index(Request $request)
    {
        // Validate query param
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:100'
        ]);

        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

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

    // ==========================
    // ➕ Thêm user (anti spam + text filter)
    // ==========================
    public function store(Request $request)
    {
        // Loại bỏ khoảng trắng độc hại
        $request->merge([
            'username' => trim($request->username ?? ''),
            'email'    => trim($request->email ?? ''),
        ]);

        $validated = $request->validate([
            'username' => ['required','max:50','unique:users','regex:/^[a-zA-Z0-9_\-\. ]+$/'],
            'email'    => 'required|email|max:100|unique:users',
            'password' => 'required|min:6|max:50',
            'role'     => 'required|in:admin,staff,customer',
            'phone'    => ['nullable','regex:/^[0-9]{10,12}$/'],
            'address'  => 'nullable|string|max:255',
            'image'    => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ],[
            'username.regex' => 'Username chứa ký tự không hợp lệ',
            'phone.regex' => 'Số điện thoại phải là số hợp lệ'
        ]);

        // Chống double click (DB transaction)
        return DB::transaction(function () use ($request, $validated) {

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/users'), $fileName);
                $validated['image_url'] = 'uploads/users/' . $fileName;
            }

            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);

            $user = User::create($validated);

            if (!empty($user->image_url)) {
                $user->image_url = asset($user->image_url);
            }

            return response()->json($user, 201);
        });
    }

    // ==========================
    // ✏️ Update (chống update trùng / stale data)
    // ==========================
    public function update(Request $request, $id)
    {
        // Validate ID
        if (!is_numeric($id)) {
            return response()->json(['message' => 'ID không hợp lệ'], 400);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy user'], 404);
        }

        // Check optimistic locking
        if ($request->filled('updated_at')) {
            if ($request->updated_at !== $user->updated_at->toISOString()) {
                return response()->json([
                    'message' => 'Dữ liệu đã thay đổi, vui lòng tải lại trang trước khi cập nhật'
                ], 409);
            }
        }

        // Clean input
        $request->merge([
            'username' => isset($request->username) ? trim($request->username) : null,
            'email'    => isset($request->email) ? trim($request->email) : null,
        ]);

        $validated = $request->validate([
            'username' => 'sometimes|required|unique:users,username,' . $id . ',user_id|max:50',
            'email'    => 'sometimes|required|email|unique:users,email,' . $id . ',user_id|max:100',
            'password' => 'nullable|min:6|max:50',
            'role'     => 'sometimes|required|in:admin,staff,customer',
            'phone'    => ['nullable','regex:/^[0-9]{10,12}$/'],
            'address'  => 'nullable|string|max:255',
            'image'    => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
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

        if (!empty($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $user->update($validated);

        if (!empty($user->image_url)) {
            $user->image_url = asset($user->image_url);
        }

        return response()->json($user);
    }

    // ==========================
    // 🔍 Show (ID validate)
    // ==========================
    public function show($id)
    {
        if (!is_numeric($id)) {
            return response()->json(['message' => 'Không tìm thấy trang'], 404);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy trang'], 404);
        }

        if ($user->image_url && !preg_match('/^https?:\/\//', $user->image_url)) {
            $user->image_url = asset($user->image_url);
        }

        $user->phone = $user->phone ?? '';
        $user->address = $user->address ?? '';

        return response()->json($user);
    }

    // ==========================
    // 🗑️ Delete (chống delete trực tiếp URL)
    // ==========================
    public function destroy(Request $request, $id)
    {
        if (!$request->isMethod('delete')) {
            return response()->json(['message' => 'Phương thức không hợp lệ'], 405);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Bản ghi không tồn tại hoặc đã bị xóa'], 404);
        }

        if ($user->image_url && file_exists(public_path($user->image_url))) {
            unlink(public_path($user->image_url));
        }

        $user->delete();

        return response()->json(['message' => 'User đã được xóa'], 200);
    }

    // ==========================
    // 📄 Export PDF
    // ==========================
    public function exportPDF()
    {
        $users = User::all();

        $pdf = Pdf::loadView('pdf.users', compact('users'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('DanhSachUsers.pdf');
    }
}
