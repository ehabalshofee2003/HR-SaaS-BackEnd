<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CheckInResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'check_in'  => Carbon::parse($this->check_in)->format('Y-m-d H:i:s'),
            'status'    => $this->status,
            'type'      => $this->type,
        ];
    }
}