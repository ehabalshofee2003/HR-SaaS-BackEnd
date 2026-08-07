<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_user_id' => 'required|exists:users,id',
            'type'             => 'required|in:check_in,check_out',
            'time'             => 'required|date_format:Y-m-d H:i:s', // السماح بتغيير الوقت
            'notes'            => 'required|string|max:500', // لماذا لا يستخدم QR؟
        ];
    }
}