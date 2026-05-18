<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
              'name' => 'required|string|min:3|max:255',

            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{10,15}$/',
                'unique:users,phone_number'
            ],

            'email' => 'nullable|email|unique:users,email',

            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ];
    }
      public function messages(): array
    {
        return [
            'phone_number.regex' => 'رقم الهاتف يجب أن يكون أرقام فقط بين 10 و 15 رقم',
            'password.min' => 'كلمة المرور يجب أن تكون 8 محارف على الأقل',
            // 'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            // 'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف كبير وصغير ورقم',
        ];
    }
}
