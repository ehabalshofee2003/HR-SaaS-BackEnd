<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Organization\Company;
use App\Models\Identity\User;
use App\Models\Hr\ExceptionType;

class ExceptionRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'exception_type_id',
        'request_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'attachment',
        'status',
        'approver_id',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

 

 

    public function exceptionType()
    {
        return $this->belongsTo(ExceptionType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_user_id'); }
 }