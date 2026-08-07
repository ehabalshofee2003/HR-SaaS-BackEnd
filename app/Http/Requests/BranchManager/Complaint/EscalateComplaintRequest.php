<?php

namespace App\Http\Requests\BranchManager\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class EscalateComplaintRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['note' => 'required|string|max:500'];
    }
}