<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => Carbon::parse($this->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($this->end_date)->format('Y-m-d'),
            'is_read' => $this->reads->isNotEmpty(), // التحقق من وجود سجل قراءة
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}