<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Http\Requests\UpdateProfileRequest; // Import Request mới
use App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    // Xem thông tin
    public function show()
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource(Auth::user())
        ], 200);
    }

    // Cập nhật thông tin
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        // Lấy dữ liệu đã được validate & sanitize
        // Lưu ý: strip_tags vẫn nên dùng nếu bạn muốn chắc chắn loại bỏ HTML
        $data = $request->validated();
        
        $user->full_name = strip_tags($data['full_name'] ?? $user->full_name);
        $user->address = strip_tags($data['address'] ?? $user->address);
        $user->phone = strip_tags($data['phone']);
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công',
            'user' => new UserResource($user)
        ], 200);
    }

    // Xóa tài khoản (Giữ nguyên logic của bạn vì đã tốt rồi)
    public function destroy()
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Xóa ảnh
            if ($user->image_url && Storage::disk('public')->exists($user->image_url)) {
                Storage::disk('public')->delete($user->image_url);
            }

            // Xóa token & user
            $user->tokens()->delete();
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Xóa tài khoản thành công'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete account error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi hệ thống, vui lòng thử lại sau.'
            ], 500);
        }
    }

    //Đổi mật khẩu
    public function changePassword(Request $request) 
    {
        $user = $request->user(); 

        // (THÊM: Import 'use Illuminate\Validation\Rules\Password;' ở đầu file)
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required', 
                'string', 
                'confirmed', 
                \Illuminate\Validation\Rules\Password::min(8) // Dùng rule có sẵn cho an toàn
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 1. Kiểm tra Mật khẩu cũ
        if (!Hash::check($request->current_password, $user->password_hash)) { 
            return response()->json([
                'errors' => [
                    'current_password' => ['Mật khẩu cũ không chính xác.']
                ]
            ], 422); 
        }

        // 2. === THÊM MỚI: KIỂM TRA TRÙNG MẬT KHẨU ===
        // Kiểm tra xem mật khẩu MỚI (plain-text) có khớp với mật khẩu CŨ (đã hash) không.
        if (Hash::check($request->new_password, $user->password_hash)) {
            return response()->json([
                'errors' => [
                    // Trả về lỗi cho trường 'new_password'
                    'new_password' => ['Mật khẩu mới không được trùng với mật khẩu cũ.']
                ]
            ], 422);
        }

        // 3. Cập nhật mật khẩu mới
        $user->password_hash = Hash::make($request->new_password); 
        $user->save();

        return response()->json([
            'message' => 'Đổi mật khẩu thành công!'
        ], 200);
    }

    
    //Upload avatar
    public function uploadAvatar(Request $request)
    {
        $user = Auth::user();

        // Validation
        $validator = Validator::make($request->all(), [
            'avatar' => [
                'required',
                'image', // jpg, jpeg, png, bmp, gif, svg, webp
                'mimes:jpeg,jpg,png,gif',
                'max:5120', // 5MB = 5120 KB
            ],
        ], [
            'avatar.required' => 'Vui lòng chọn ảnh',
            'avatar.image' => 'File phải là ảnh',
            'avatar.mimes' => 'Ảnh phải có định dạng: jpeg, jpg, png, gif',
            'avatar.max' => 'Ảnh không được vượt quá 5MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Xóa avatar cũ nếu có
            if ($user->image_url && Storage::disk('public')->exists($user->image_url)) {
                Storage::disk('public')->delete($user->image_url);
            }

            // Lưu avatar mới
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->user_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');

            // Update DB
            $user->image_url = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Upload avatar thành công',
                'user' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'full_name' => $user->full_name,
                    'address' => $user->address,
                    'image_url' => $user->image_url,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Upload avatar error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload thất bại. Vui lòng thử lại.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}