<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->check_in)->format('Y-m-d'),
            'status' => $this->status,
            'check_in' => Carbon::parse($this->check_in)->format('H:i:s'),
            'check_out' => $this->check_out ? Carbon::parse($this->check_out)->format('H:i:s') : null,
            'work_hours' => (float) $this->work_hours,
        ];
    }
}