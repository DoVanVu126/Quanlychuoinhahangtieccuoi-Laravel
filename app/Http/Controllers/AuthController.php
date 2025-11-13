<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\DB; // Cho Transaction
use Illuminate\Support\Facades\Hash; // Cho mã hóa mật khẩu
use Illuminate\Support\Facades\Validator; // Cho kiểm tra dữ liệu
use Illuminate\Support\Facades\Log; // Để ghi log lỗi
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use App\Models\PasswordResetOtp; // <-- Model mới
use Illuminate\Support\Facades\Mail; // <-- Dùng để gửi mail
use App\Mail\SendOtpMail;           // <-- Lát nữa chúng ta sẽ tạo
use Carbon\Carbon; 

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

    //Đăng nhập
    /**
     * Xử lý yêu cầu đăng nhập và trả về token.
     * (Phiên bản 2: Kiểm tra thủ công, không dùng Auth::attempt)
     */
    public function login(Request $request)
    {
        // 1. Validate (Giữ nguyên)
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('login', 'password');

        // --- BẮT ĐẦU LOGIC MỚI ---

        // 2. TÌM USER: Tìm user có 'username' HOẶC 'email' khớp với 'login'
        $user = User::where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login'])
                    ->first();

        // 3. KIỂM TRA MẬT KHẨU
        //    Dùng Hash::check() để so sánh (mật_khẩu_user_nhập, mật_khẩu_đã_hash_trong_DB)
        
        // Nếu $user không tồn tại HOẶC Hash::check thất bại (mật khẩu sai)
        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            
            // Trả về lỗi 422 (Unprocessable Content)
            return response()->json([
                'errors' => ['login' => ['Tài khoản hoặc mật khẩu không chính xác.']]
            ], 422);
        }

        // 4. ĐĂNG NHẬP THÀNH CÔNG
        // (User đã tồn tại VÀ mật khẩu đã khớp)
        
        // (Tùy chọn) Đăng nhập user vào session (để các hàm Auth::user() sau này hoạt động)
        Auth::login($user);

        // Tạo token Sanctum mới
        $token = $user->createToken('api-token-cho-spa')->plainTextToken;

        // 5. Trả về token và thông tin user (frontend đang mong đợi)
        return response()->json([
            'user' => $user,
            'access_token' => $token
        ]);

        // --- KẾT THÚC LOGIC MỚI ---
    }
}