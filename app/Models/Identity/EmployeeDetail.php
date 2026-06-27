<?php

namespace App\Models\Identity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Organization\Department;

class EmployeeDetail extends Model
{
    use SoftDeletes; // لأن المايجريشن لديك يحتوي على deleted_at

    // تحديد الحقول القابلة للتعبئة (تطبيقاً للمعايير)
    protected $fillable = [
        'user_id',
        'department_id',
        'job_title',
        'employment_status',
        'hire_date',
    ];

    // تحويل التواريخ
    protected $casts = [
        'hire_date' => 'date',
    ];

    /*
    |------------------------------------------------------------------
    | العلاقات (Relationships)
    |------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        // ملاحظة: مسار الموديل هو Organization وليس Hr
        return $this->belongsTo(Department::class, 'department_id');
    }
}