<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * جميع الصلاحيات الممكنة بالنظام، مقسّمة حسب النطاق (Scope).
     * كل صلاحية بتنعرّف مرة وحدة فقط بجدول permissions،
     * وبعدين بتتوزع على الأدوار (roles) كـ "سقف نظري" (Template).
     */
    protected array $branchManagerPermissions = [
        // Departments
        'departments.view', 'departments.create', 'departments.update', 'departments.delete',
        // Supervisors
        'supervisors.view', 'supervisors.create', 'supervisors.update', 'supervisors.delete', 'supervisors.assign',
        // Employees
        'employees.view', 'employees.create', 'employees.update', 'employees.delete', 'employees.documents.manage',
        // Attendance
        'attendance.view', 'attendance.manual_entry', 'attendance.export',
        // Tasks
        'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
        // Evaluations
        'evaluations.view', 'evaluations.create', 'evaluations.review',
        // Leaves
        'leaves.view', 'leaves.approve', 'leaves.reject',
        // Exception Requests
        'exceptions.view', 'exceptions.forward', 'exceptions.reject',
        // Payroll
        'payroll.view', 'payroll.calculate', 'payroll.approve', 'payroll.mark_paid', 'payroll.export',
        // Complaints
        'complaints.view', 'complaints.respond', 'complaints.escalate', 'complaints.resolve',
        // Resignations
        'resignations.view', 'resignations.approve', 'resignations.reject',
        // Announcements
        'announcements.view', 'announcements.create', 'announcements.delete',
        // Workshops
        'workshops.view', 'workshops.create', 'workshops.update', 'workshops.manage_attendance',
        // Reports
        'reports.view',
        // Notifications
        'notifications.view',
        // Dashboard
        'dashboard.view',
        // Account & Settings
        'settings.view', 'settings.update',
    ];

    protected array $supervisorPermissions = [
        'employees.view', 'employees.update',
        'attendance.view', 'attendance.manual_entry',
        'tasks.view', 'tasks.create', 'tasks.update',
        'leaves.view', 'leaves.approve',
        'exceptions.view', 'exceptions.forward',
        'evaluations.view', 'evaluations.create',
        'notifications.view',
        'settings.view',
    ];

    public function run(): void
    {
        // تفريغ الكاش المحلي لصلاحيات Spatie قبل البدء
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // دمج كل الصلاحيات الفريدة (بدون تكرار) وإنشاؤها دفعة وحدة
        $allPermissions = array_unique(array_merge(
            $this->branchManagerPermissions,
            $this->supervisorPermissions
        ));

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        // إنشاء الأدوار وربطها بالسقف النظري لكل دور
        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => $guard,
        ]);
        // $managerRole->syncPermissions($this->branchManagerPermissions);

        $supervisorRole = Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => $guard,
        ]);
        // $supervisorRole->syncPermissions($this->supervisorPermissions);

        // الأدوار الأعلى (owner, super_admin) بنعرّفها هون بدون صلاحيات مفصّلة الآن
        // (رح تُبنى صلاحياتها بمرحلة لاحقة عند تطوير باك إند الـ Owner)
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
    }
}