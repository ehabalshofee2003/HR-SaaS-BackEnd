<?php

namespace App\Http\Requests\BranchManager\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class ManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_user_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'status' => 'required|in:present,late,absent,early_leave',
            'reason' => 'required|string|max:500',
        ];
    }
}