<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Mail\OtpMail;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Bước 1: Gửi OTP qua email
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        
        // Tạo mã OTP 6 số
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // --- SỬA ĐỔI: Dùng bảng password_reset_tokens mặc định ---
        
        // Xóa token cũ
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Lưu OTP mới (Lưu vào cột 'token', Hash để bảo mật)
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => Hash::make($otpCode), // Dùng cột 'token' thay vì 'otp_code'
            'created_at' => Carbon::now()    // Dùng 'created_at' để tính hạn
        ]);

        // Gửi email
        try {
            Mail::to($email)->send(new OtpMail($otpCode));
            
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email của bạn',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi gửi mail: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Bước 2: Xác thực OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        $otpCode = $request->otp_code;

        // --- SỬA ĐỔI: Tìm trong bảng password_reset_tokens ---
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không tồn tại'], 404);
        }

        // --- SỬA ĐỔI: Kiểm tra hết hạn dựa trên created_at (ví dụ 5 phút) ---
        if (Carbon::parse($record->created_at)->addMinutes(5)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return response()->json(['success' => false, 'message' => 'Mã OTP đã hết hạn'], 410);
        }

        // Kiểm tra khớp mã (So sánh Hash của cột token)
        if (!Hash::check($otpCode, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không chính xác'], 401);
        }

        return response()->json(['success' => true, 'message' => 'OTP hợp lệ', 'reset_token' => $otpCode], 200);
    }

    /**
     * Bước 3: Đặt lại mật khẩu
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6', // Frontend gửi lên với tên key là 'otp' hoặc 'otp_code' tùy bạn sửa, ở đây tôi để 'otp' cho khớp logic verify
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        // Lưu ý: Ở Bước 2 frontend trả về reset_token chính là otp_code.
        // Nên ở bước này request sẽ gửi lên: email, otp (là cái reset_token), password.

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        $otpCode = $request->otp; // Frontend gửi lên key là 'otp' (giá trị là reset_token lưu trong localStorage)

        // Kiểm tra lại lần cuối
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($otpCode, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Yêu cầu không hợp lệ'], 400);
        }

        // Cập nhật mật khẩu
        $user = User::where('email', $email)->first();
        $user->update([
            'password_hash' => Hash::make($request->password)
        ]);

        // Xóa token đã dùng
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json(['success' => true, 'message' => 'Đặt lại mật khẩu thành công'], 200);
    }
}