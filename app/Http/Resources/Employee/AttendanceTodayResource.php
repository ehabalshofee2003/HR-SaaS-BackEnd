<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceTodayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'check_in' => Carbon::parse($this->check_in)->format('H:i:s'),
            'check_out' => $this->check_out ? Carbon::parse($this->check_out)->format('H:i:s') : null,
            'work_hours' => $this->work_hours,
            'is_active' => is_null($this->check_out), // مهم للـ Frontend ليعرف هل يظهر زر Check-out
        ];
    }
}