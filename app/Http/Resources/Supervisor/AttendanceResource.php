<?php

namespace App\Http\Resources\Supervisor;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lateMinutes = 0;
        if ($this->status === 'late' && $this->check_in) {
            $checkIn = Carbon::parse($this->check_in);
            $officialStart = $checkIn->copy()->setTime(8, 0);
            $lateMinutes = $checkIn->gt($officialStart) ? $officialStart->diffInMinutes($checkIn) : 0;
        }

        // تحديد نوع حركة التعديل تلقائياً من الطلب أو بناءً على وجود وقت الخروج
        $entryType = $request->input('entry_type') 
            ?? ($this->check_out ? 'check_out' : 'check_in');

        return [
            'id' => $this->id,
            'date' => $this->check_in 
                ? Carbon::parse($this->check_in)->format('d-m-Y') 
                : now()->format('d-m-Y'),
            'entry_type' => $entryType,
            'type' => strtoupper($this->type) === 'QR' ? 'QR' : 'manual',
            'status' => $this->status,
            'check_in' => $this->check_in ? Carbon::parse($this->check_in)->format('H:i') : null,
            'check_out' => $this->check_out ? Carbon::parse($this->check_out)->format('H:i') : null,
            'working_hours' => (float) ($this->work_hours ?? 0),
            'lateness_minutes' => (int) $lateMinutes,
            'reason' => $this->notes,
        ];
    }
}