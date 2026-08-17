<?php

namespace App\Http\Requests\Supervisor\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:all,present,absent,late'],
        ];
    }
}