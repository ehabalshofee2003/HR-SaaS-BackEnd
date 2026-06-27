<?php

namespace App\Models\Organization;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Hr\AttendanceLog;
use App\Models\Hr\QrCode;
use App\Models\Hr\Workshop;

class Branch extends BaseModel
{
    protected $table = 'branches';

    protected $fillable = [
        'company_id', 'manager_user_id', 'name', 'location', 'status'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class, 'branch_id');
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class, 'branch_id');
    }

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'branch_id');
    }

    public function workshops()
    {
        return $this->hasMany(Workshop::class, 'branch_id');
    }
}