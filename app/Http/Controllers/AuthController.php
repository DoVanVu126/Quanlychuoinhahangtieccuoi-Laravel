<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Xử lý yêu cầu đăng ký tài khoản với validation đầy đủ
     */
    public function register(Request $request)
    {
        // 1. Validate với rules chi tiết
        $validator = Validator::make($request->all(), [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_]+$/', // Chỉ chữ, số, gạch dưới
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]{9,11}$/', // Chỉ số, 9-11 ký tự
                'unique:users,phone',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                'regex:/^[^\<\>]*$/', // Không chứa < >
            ],
        ], [
            // Custom error messages
            'username.required' => 'Tên tài khoản là bắt buộc',
            'username.min' => 'Tên tài khoản phải có ít nhất 3 ký tự',
            'username.max' => 'Tên tài khoản không được vượt quá 50 ký tự',
            'username.unique' => 'Tên tài khoản đã tồn tại trong hệ thống',
            'username.regex' => 'Tên tài khoản chỉ được chứa chữ cái, số và dấu gạch dưới',
            
            'email.required' => 'Email là bắt buộc',
            'email.email' => 'Email không đúng định dạng',
            'email.max' => 'Email không được vượt quá 255 ký tự',
            'email.unique' => 'Email đã được đăng ký',
            
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.regex' => 'Số điện thoại chỉ được chứa số (9-11 ký tự)',
            'phone.unique' => 'Số điện thoại đã được đăng ký',
            
            'password.required' => 'Mật khẩu là bắt buộc',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
            'password.max' => 'Mật khẩu không được vượt quá 255 ký tự',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp',
            'password.regex' => 'Mật khẩu không được chứa ký tự đặc biệt < hoặc >',
        ]);

        // 2. Nếu validate thất bại, trả về lỗi
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Sanitize input (loại bỏ HTML tags)
        $cleanData = [
            'username' => strip_tags($request->username),
            'email' => strip_tags($request->email),
            'phone' => strip_tags($request->phone),
            'password' => $request->password,
        ];

        // 4. Bắt đầu Transaction
        DB::beginTransaction();

        try {
            // 5. Tạo user
            $user = User::create([
                'username' => $cleanData['username'],
                'email' => $cleanData['email'],
                'phone' => $cleanData['phone'],
                'password_hash' => Hash::make($cleanData['password']),
                'role' => 'customer'
            ]);

            // 6. Tạo customer
            Customer::create([
                'user_id' => $user->user_id 
            ]);

            // 7. Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký tài khoản thành công!',
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            // 8. Rollback nếu có lỗi
            DB::rollBack();
            Log::error('Lỗi đăng ký: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình đăng ký',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Xử lý đăng nhập với validation
     */
    public function login(Request $request)
    {
        // 1. Validate
        $validator = Validator::make($request->all(), [
            'login' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\<\>]*$/', // Không chứa HTML
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'login.required' => 'Tên tài khoản hoặc email là bắt buộc',
            'login.regex' => 'Tên tài khoản không được chứa ký tự đặc biệt',
            'password.required' => 'Mật khẩu là bắt buộc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Sanitize input
        $credentials = [
            'login' => strip_tags($request->login),
            'password' => $request->password,
        ];

        // 3. Tìm user
        $user = User::where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login'])
                    ->first();

        // 4. Kiểm tra user và password
        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản hoặc mật khẩu không chính xác',
                'errors' => ['login' => ['Tài khoản hoặc mật khẩu không chính xác']]
            ], 422);
        }

        // 5. Đăng nhập thành công
        Auth::login($user);
        $token = $user->createToken('api-token-cho-spa')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'user' => $user,
            'access_token' => $token
        ], 200);
    }
}