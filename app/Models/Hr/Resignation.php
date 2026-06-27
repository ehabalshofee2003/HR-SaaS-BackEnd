<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Identity\User;

class Resignation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resignations';

    protected $fillable = [
        'employee_user_id',
        'supervisor_user_id',
        'reason',
        'notice_date',
        'last_working_date',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'notice_date' => 'date',
        'last_working_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // علاقة لجلب اسم المشرف في الـ Resource
    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Identity\User::class, 'supervisor_user_id');
    }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }
}