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

    // Gửi OTP qua email
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email|exists:users,email']);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Xóa cũ, thêm mới vào bảng OTPS
        DB::table('password_reset_otps')->where('email', $request->email)->delete();
        DB::table('password_reset_otps')->insert([
            'email' => $request->email,
            'otp_code' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(5),
            'created_at' => now() // Thêm dòng này để tránh lỗi nếu DB yêu cầu
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\OtpMail($otpCode));
            return response()->json(['success' => true, 'message' => 'Đã gửi mã OTP.'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi gửi mail.'], 500);
        }
    }


    //Xác thực OTP

    public function verifyOtp(Request $request)
    {
        // ... (Logic cũ của bạn đã đúng, chỉ cần đảm bảo dùng 'otp_code') ...
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6'
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $record = DB::table('password_reset_otps')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp_code, $record->otp_code)) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không chính xác.'], 401);
        }
        
        return response()->json(['success' => true, 'message' => 'OTP hợp lệ', 'reset_token' => $request->otp_code], 200);
    }


    //Đặt lại mật khẩu
    
    public function resetPassword(Request $request)
    {
        // 1. Sửa tên tham số thành 'otp_code' cho khớp Frontend
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6', 
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $email = $request->email;
        $otpCode = $request->otp_code;

        // 2. Sửa tên bảng thành 'password_reset_otps' (thay vì tokens)
        $record = DB::table('password_reset_otps')->where('email', $email)->first();

        // 3. Kiểm tra OTP (Dùng cột 'otp_code')
        if (!$record || !Hash::check($otpCode, $record->otp_code)) {
            return response()->json(['success' => false, 'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // 4. Cập nhật mật khẩu (Dùng password_hash)
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update([
                'password_hash' => Hash::make($request->password)
            ]);
        }

        // 5. Xóa OTP đã dùng
        DB::table('password_reset_otps')->where('email', $email)->delete();

        return response()->json(['success' => true, 'message' => 'Đặt lại mật khẩu thành công'], 200);
    }
}