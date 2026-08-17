<?php

namespace App\Http\Requests\Supervisor\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}