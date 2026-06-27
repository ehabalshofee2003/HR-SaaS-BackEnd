<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ResignationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'notice_date' => Carbon::parse($this->notice_date)->format('Y-m-d'),
            'last_working_date' => Carbon::parse($this->last_working_date)->format('Y-m-d'),
            'status' => $this->status,
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->profile?->first_name . ' ' . $this->supervisor->profile?->last_name,
                ];
            }),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}