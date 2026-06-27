<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Resources\Json\JsonResource;

class PayrollComponentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'component_type' => $this->component_type, // base_salary, bonus, deduction, allowance, overtime
            'amount' => (float) $this->amount,        ];
    }
}