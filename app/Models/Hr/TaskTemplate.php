<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Organization\Department;

class TaskTemplate extends BaseModel
{
    protected $table = 'task_templates';
    protected $fillable = ['department_id', 'name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'template_id');
    }
}