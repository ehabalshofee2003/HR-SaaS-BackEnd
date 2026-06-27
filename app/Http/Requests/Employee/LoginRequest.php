<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // نسمح للموظف بالدخول بالإيميل أو الهاتف
            'account' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}