<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class HomeTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'type'        => $this->type,
            'priority'    => $this->priority,
            'status'      => $this->status,
            'due_date'    => Carbon::parse($this->due_date)->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at
                ? Carbon::parse($this->completed_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}