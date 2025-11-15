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
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'exists:users,email',
                'regex:/^[^\<\>]*$/',
            ],
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email không được vượt quá 255 ký tự',
            'email.exists' => 'Email không tồn tại trong hệ thống',
            'email.regex' => 'Email không hợp lệ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strip_tags($request->email);
        
        // Tạo mã OTP 6 số
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Thời gian hết hạn: 5 phút
        $expiresAt = Carbon::now()->addMinutes(5);

        // Xóa OTP cũ nếu có
        DB::table('password_reset_otps')->where('email', $email)->delete();

        // Lưu OTP mới
        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp_code' => Hash::make($otpCode),
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
            \Log::error('Send OTP Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bước 2: Xác thực OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'regex:/^[^\<\>]*$/',
            ],
            'otp_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.regex' => 'Email không hợp lệ',
            'otp_code.required' => 'Mã OTP là bắt buộc',
            'otp_code.size' => 'Mã OTP phải có đúng 6 ký tự',
            'otp_code.regex' => 'Mã OTP chỉ được chứa số',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strip_tags($request->email);
        $otpCode = strip_tags($request->otp_code);

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
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'regex:/^[^\<\>]*$/',
            ],
            'otp_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                'regex:/^[^\<\>]*$/',
            ],
        ], [
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.regex' => 'Email không hợp lệ',
            'otp_code.required' => 'Mã OTP là bắt buộc',
            'otp_code.size' => 'Mã OTP phải có đúng 6 ký tự',
            'otp_code.regex' => 'Mã OTP chỉ được chứa số',
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.max' => 'Mật khẩu không được vượt quá 255 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.regex' => 'Mật khẩu không được chứa ký tự đặc biệt < hoặc >',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $email = strip_tags($request->email);
        $otpCode = strip_tags($request->otp_code);

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

        // Tìm user và cập nhật mật khẩu
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại',
            ], 404);
        }

        // Cập nhật mật khẩu mới
        $user->password_hash = Hash::make($request->password);
        $user->save();

        // Xóa OTP đã sử dụng
        DB::table('password_reset_otps')->where('email', $email)->delete();

        \Log::info('Password reset successful for: ' . $email);

        return response()->json([
            'success' => true,
            'message' => 'Đặt lại mật khẩu thành công',
        ], 200);
    }
}