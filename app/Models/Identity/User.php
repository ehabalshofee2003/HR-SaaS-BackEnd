<?php

namespace App\Models\Identity;

use App\Models\BaseModel;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable; // <-- تأكد من وجود هذا السطر
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles , SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'phone',
        'password_hash',
        'user_type',
        'status',
        'two_factor_enabled',
        'last_login_at',
        'branch_id',
    ];

    protected $hidden = [
        'password_hash', 'remember_token',
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // --- Identity Relations ---
    public function profile() {
        return $this->hasOne(UserProfile::class, 'user_id');
    }
    public function employee() {
        return $this->hasOne(EmployeeDetail::class);
    }
    public function employeeDetail() {
        return $this->hasOne(EmployeeDetail::class, 'user_id');
    }
    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class, 'user_id');
    }
    // --- Organization Relations ---
    public function ownedCompany(){
        return $this->hasOne(\App\Models\Organization\Company::class, 'owner_user_id');
    }

    public function managedBranch(){
        return $this->hasOne(\App\Models\Organization\Branch::class, 'manager_user_id');
    }

    public function supervisedDepartments(){
        return $this->hasMany(\App\Models\Organization\Department::class, 'supervisor_user_id');
    }

    // --- HR Relations (Tasks) ---
    public function assignedTasks(){
        return $this->hasMany(\App\Models\Hr\Task::class, 'employee_user_id');
    }

    public function createdTasks(){
        return $this->hasMany(\App\Models\Hr\Task::class, 'supervisor_user_id');
    }

    // --- HR Relations (Leave Requests) ---
    public function leaveRequests()
    {
        return $this->hasMany(\App\Models\Hr\LeaveRequest::class, 'employee_user_id');
    }

    public function supervisedLeaveRequests()
    {
        return $this->hasMany(\App\Models\Hr\LeaveRequest::class, 'supervisor_user_id');
    }

    // --- Payroll Relations ---
    public function payrollRecords()
    {
        return $this->hasMany(\App\Models\Payroll\PayrollRecord::class, 'employee_user_id');
    }

    public function salary()
    {
        return $this->hasOne(\App\Models\Payroll\EmployeeSalary::class, 'employee_user_id');
    }
    public function getCurrentBranchId(): ?int
    {
        return $this->branch_id;
    }
        /**
     /**
     * يحصل على معرف الشركة التابع لها المستخدم بغض النظر عن عمق التسلسل الهرمي
     */
public function getCurrentCompanyId(): ?int
{
    // مسار الموظف العادي (له سجل employee_details)
    if ($this->employeeDetail) {
        return $this->employeeDetail->department?->branch?->company_id;
    }

    // مسار مدير الفرع أو المشرف (بس عندهم branch_id مباشر، بدون employeeDetail)
    if ($this->branch_id) {
        return \App\Models\Organization\Branch::find($this->branch_id)?->company_id;
    }

    // مسار المالك (Owner)
    if ($this->ownedCompany) {
        return $this->ownedCompany->id;
    }

    return null;
}
}