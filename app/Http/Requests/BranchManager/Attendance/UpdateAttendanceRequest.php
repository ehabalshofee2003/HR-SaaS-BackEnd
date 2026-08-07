<?php

namespace App\Http\Requests\BranchManager\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'check_in' => 'sometimes|required|date_format:H:i',
            'check_out' => 'sometimes|nullable|date_format:H:i',
            'status' => 'sometimes|required|in:present,late,absent,early_leave',
            'reason' => 'required|string|max:500',
        ];
    }
}