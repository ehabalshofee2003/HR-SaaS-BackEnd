<?php

namespace App\Http\Resources\Supervisor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total_employees' => $this->resource['stats']['total_employees'],
                'present_today' => $this->resource['stats']['present_today'],
                'absent_today' => $this->resource['stats']['absent_today'],
                'attendance_percentage' => $this->resource['stats']['attendance_percentage'],
                'pending_tasks' => $this->resource['stats']['pending_tasks'],
                'pending_leaves' => $this->resource['stats']['pending_leaves'],
                'avg_evaluation' => $this->resource['stats']['avg_evaluation'],
            ],
            'charts' => [
                'weekly_attendance' => $this->resource['weekly_attendance'],
                'tasks_distribution' => $this->resource['tasks_distribution'],
            ],
            'lists' => [
                'overdue_tasks' => $this->resource['overdue_tasks'],
                'recent_leaves' => $this->resource['recent_leaves'],
            ],
            // هذه البيانات ضرورية جداً لفريق الفرونت لبناء الأزرار ديناميكياً
            'ui_permissions' => [
                'can_record_attendance' => in_array('record_attendance', $this->resource['permissions']),
                'can_create_task' => in_array('create_task', $this->resource['permissions']),
                'can_evaluate' => in_array('evaluate_employee', $this->resource['permissions']),
            ]
        ];
    }
}