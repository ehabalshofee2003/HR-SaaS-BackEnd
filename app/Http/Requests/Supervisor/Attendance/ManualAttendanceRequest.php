<?php

namespace App\Http\Requests\Supervisor\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'in:check_in,check_out'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}