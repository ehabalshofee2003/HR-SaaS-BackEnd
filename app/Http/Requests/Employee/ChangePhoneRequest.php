<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\Form\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChangePhoneRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'phone' => 'required|string|unique:users,phone,' . Auth::id(),
        ];
    }
}