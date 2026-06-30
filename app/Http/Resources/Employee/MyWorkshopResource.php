<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class MyWorkshopResource extends JsonResource
{
    // هنا الـ Resource مبني على الـ Pivot Table
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->workshop->id,
            'title'         => $this->workshop->title,
            'location'      => $this->workshop->location,
            'start_date'    => Carbon::parse($this->workshop->start_date)->format('Y-m-d H:i'),
            'end_date'      => Carbon::parse($this->workshop->end_date)->format('Y-m-d H:i'),
            'workshop_status' => $this->workshop->status,
            'my_status'     => $this->status, // registered, attended, absent...
            'registered_at' => Carbon::parse($this->registered_at)->format('Y-m-d H:i:s'),
        ];
    }
}