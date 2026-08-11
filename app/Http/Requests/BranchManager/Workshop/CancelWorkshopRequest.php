<?php

namespace App\Http\Requests\BranchManager\Workshop;
use Illuminate\Foundation\Http\FormRequest;
 
class CancelWorkshopRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['reason' => 'required|string|max:500'];
    }
}