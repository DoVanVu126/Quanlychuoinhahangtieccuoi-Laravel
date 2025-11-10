<?php

namespace App\Http\Controllers; // <-- Đảm bảo namespace đúng

// Import tất cả các lớp cần thiết
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\DB; // Cho Transaction
use Illuminate\Support\Facades\Hash; // Cho mã hóa mật khẩu
use Illuminate\Support\Facades\Validator; // Cho kiểm tra dữ liệu
use Illuminate\Support\Facades\Log; // Để ghi log lỗi

class AuthController extends Controller
{
    /**
     * Xử lý yêu cầu đăng ký tài khoản (Tạo User và Customer).
     */
    public function register(Request $request)
    {
        // 1. Validate 4 trường bắt buộc
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' yêu cầu phải có 'password_confirmation'
        ]);

        // Nếu validate thất bại, trả về lỗi
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // 422 Unprocessable Entity
        }

        // Bắt đầu một Transaction
        DB::beginTransaction();

        

        try {
            // 2. TẠO BẢN GHI TRONG BẢNG 'users'
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password), // Dùng đúng tên cột 'password_hash'
                'role' => 'customer' // Tự động gán vai trò là customer
            ]);

            // 3. TẠO BẢN GHI TRONG BẢNG 'customers'
            // Lấy 'user_id' từ user vừa tạo ở trên
            Customer::create([
                'user_id' => $user->user_id 
            ]);

            // 4. Nếu cả 2 đều thành công, xác nhận (commit) Transaction
            DB::commit();

            return response()->json([
                'message' => 'Đăng ký tài khoản customer thành công!',
                'user' => $user // Trả về thông tin user (đã ẩn password_hash)
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // 5. Nếu có bất kỳ lỗi nào ở B2 hoặc B3, hủy bỏ (rollback) tất cả
            DB::rollBack();

            // Ghi lại log lỗi (quan trọng để debug)
            Log::error('Lỗi đăng ký: ' . $e->getMessage());

            return response()->json([
                'message' => 'Đã có lỗi xảy ra trong quá trình đăng ký.',
                'error' => $e->getMessage() // Chỉ hiển thị lỗi chi tiết khi đang ở môi trường dev
            ], 500); // 500 là lỗi server
        }
    }
}