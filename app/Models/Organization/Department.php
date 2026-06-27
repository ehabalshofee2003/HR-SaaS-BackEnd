<?php

namespace App\Models\Organization;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Identity\EmployeeDetail;
use App\Models\Hr\Complaint;
use App\Models\Hr\TaskTemplate;

class Department extends BaseModel
{
    protected $table = 'departments';

    protected $fillable = [
        'branch_id', 'supervisor_user_id', 'name', 'status'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function employees()
    {
        return $this->hasMany(EmployeeDetail::class, 'department_id');
    }

    public function taskTemplates()
    {
        return $this->hasMany(TaskTemplate::class, 'department_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'department_id');
    }
}