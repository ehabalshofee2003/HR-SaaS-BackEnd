<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',         // مثال: سنوية، مرضية، بدون راتب
        'code',         // مثال: ANNUAL, SICK, UNPAID
        'description',
        'is_paid',      // هل الإجازة مدفوعة الراتب؟
        'max_days_per_year', // الحد الأقصى للأيام في السنة
        'requires_attachment', // هل تتطلب مرفق (مثل تقرير طبي)؟
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
        'is_active' => 'boolean',
    ];

    // علاقة عكسية: نوع الإجازة لديه العديد من طلبات الإجازات
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'leave_type_id');
    }
}