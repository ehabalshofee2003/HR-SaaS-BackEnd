<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Organization\Branch;

class QrCode extends BaseModel
{
    protected $table = 'qr_codes';
    protected $fillable = ['branch_id', 'code', 'type', 'usage_limit', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'qr_code_id');
    }
}