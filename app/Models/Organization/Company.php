<?php

namespace App\Models\Organization;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\SaaS\CompanySubscription;
use App\Models\SaaS\CompanySetting;
use App\Models\SaaS\Invoice;
use App\Models\Hr\LeavePolicy;
use App\Models\Hr\Task;
use App\Models\Hr\Complaint;
use App\Models\Hr\Workshop;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\SalaryTemplate;
use App\Models\Support\Announcement;
use App\Models\Support\Notification;
use App\Models\Support\SupportTicket;

class Company extends BaseModel
{
    protected $table = 'companies';

    protected $fillable = [
        'owner_user_id', 'name', 'logo', 'tax_number', 'currency_code', 'status'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'company_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class, 'company_id');
    }

    public function settings()
    {
        return $this->hasMany(CompanySetting::class, 'company_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'company_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'company_id');
    }

    public function leavePolicies()
    {
        return $this->hasMany(LeavePolicy::class, 'company_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'company_id');
    }

    public function workshops()
    {
        return $this->hasMany(Workshop::class, 'company_id');
    }

    public function payrollPeriods()
    {
        return $this->hasMany(PayrollPeriod::class, 'company_id');
    }

    public function salaryTemplates()
    {
        return $this->hasMany(SalaryTemplate::class, 'company_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'company_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'company_id');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'company_id');
    }
}