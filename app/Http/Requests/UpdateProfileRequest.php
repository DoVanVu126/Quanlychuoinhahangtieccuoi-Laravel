<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = Auth::id(); // Hoặc Auth::user()->user_id

        return [
            'full_name' => ['nullable', 'string', 'max:100', 'regex:/^[^\<\>]*$/'],
            'address'   => ['nullable', 'string', 'max:255', 'regex:/^[^\<\>]*$/'],
            'phone'     => [
                'required', 
                'string', 
                'regex:/^[0-9]{9,11}$/', 
                // Cú pháp hiện đại, dễ đọc hơn string
                Rule::unique('users', 'phone')->ignore($userId, 'user_id') 
            ],
        ];
    }
    
    // Bạn có thể override hàm messages() để tùy chỉnh thông báo lỗi tại đây
}