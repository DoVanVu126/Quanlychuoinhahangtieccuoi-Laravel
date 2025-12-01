<?php

namespace App\Http\Controllers\Api;

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
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'email.exists' => 'Email không tồn tại trong hệ thống',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        
        // Tạo mã OTP 6 số
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Thời gian hết hạn: 5 phút
        $expiresAt = Carbon::now()->addMinutes(5);

        // Xóa OTP cũ nếu có
        DB::table('password_reset_otps')->where('email', $email)->delete();

        // Lưu OTP mới
        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp_code' => Hash::make($otpCode), // Hash OTP để bảo mật
            'expires_at' => $expiresAt,
        ]);

        // Gửi email
        try {
            Mail::to($email)->send(new OtpMail($otpCode, 5));
            
            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi đến email của bạn',
                'expires_at' => $expiresAt->toDateTimeString(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => $e->getMessage()
            ], 500);
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
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'otp_code.required' => 'Mã OTP là bắt buộc',
            'otp_code.size' => 'Mã OTP phải có 6 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $otpCode = $request->otp_code;

        // Tìm OTP trong database
        $otpRecord = DB::table('password_reset_otps')
            ->where('email', $email)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không tồn tại hoặc đã hết hạn',
            ], 404);
        }

        // Kiểm tra thời gian hết hạn
        if (Carbon::parse($otpRecord->expires_at)->isPast()) {
            DB::table('password_reset_otps')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.',
            ], 410);
        }

        // Xác thực OTP
        if (!Hash::check($otpCode, $otpRecord->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Xác thực OTP thành công',
        ], 200);
    }

    /**
     * Bước 3: Đặt lại mật khẩu
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp_code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không hợp lệ',
            'otp_code.required' => 'Mã OTP là bắt buộc',
            'otp_code.size' => 'Mã OTP phải có 6 ký tự',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->email;
        $otpCode = $request->otp_code;

        // Tìm OTP trong database
        $otpRecord = DB::table('password_reset_otps')
            ->where('email', $email)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không tồn tại',
            ], 404);
        }

        // Kiểm tra thời gian hết hạn
        if (Carbon::parse($otpRecord->expires_at)->isPast()) {
            DB::table('password_reset_otps')->where('email', $email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP đã hết hạn',
            ], 410);
        }

        // Xác thực OTP
        if (!Hash::check($otpCode, $otpRecord->otp_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không chính xác',
            ], 401);
        }

        // Cập nhật mật khẩu mới
        $user = User::where('email', $email)->first();
        $user->update([
                'password_hash' => Hash::make($request->password) // <-- SỬA LẠI TÊN CỘT
        ]);

        // Xóa OTP đã sử dụng
        DB::table('password_reset_otps')->where('email', $email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công',
        ], 200);
    }
}