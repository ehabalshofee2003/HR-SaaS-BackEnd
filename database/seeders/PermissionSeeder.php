<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Identity\User;

class PermissionSeeder extends Seeder
{
    protected array $branchManagerPermissions = [
        'departments.view', 'departments.create', 'departments.update', 'departments.delete',
        'supervisors.view', 'supervisors.create', 'supervisors.update', 'supervisors.delete', 'supervisors.assign',
        'employees.view', 'employees.create', 'employees.update', 'employees.delete', 'employees.documents.manage',
        'attendance.view', 'attendance.manual_entry', 'attendance.export',
        'tasks.view', 'tasks.create', 'tasks.update', 'tasks.delete',
        'evaluations.view', 'evaluations.create', 'evaluations.review',
        'leaves.view', 'leaves.approve', 'leaves.reject',
        'exceptions.view', 'exceptions.forward', 'exceptions.reject',
        'payroll.view', 'payroll.calculate', 'payroll.approve', 'payroll.mark_paid', 'payroll.export',
        'complaints.view', 'complaints.respond', 'complaints.escalate', 'complaints.resolve',
        'resignations.view', 'resignations.approve', 'resignations.reject',
        'announcements.view', 'announcements.create', 'announcements.delete',
        'workshops.view', 'workshops.create', 'workshops.update', 'workshops.manage_attendance',
        'reports.view',
        'notifications.view',
        'dashboard.view',
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

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

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => $guard]);
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);

        // =====================================================
        // منح فعلي للمستخدمين التجريبيين (يعتمد على BaseUserTestSeeder/SupervisorTestSeeder)
        // =====================================================

        // مدير الفرع التجريبي: يحصل تلقائياً على كل صلاحيات Branch Manager مباشرة (منح مباشر، مو عبر Role)
        $branchManager = User::where('phone', '0798888888')->first();
        if ($branchManager) {
            $branchManager->assignRole($managerRole); // Role كـ Label فقط
            $branchManager->givePermissionTo($this->branchManagerPermissions); // المنح الفعلي مباشر
        }

        // المشرفون التجريبيون: يحصلون فقط على الـ Role كـ Label — بدون أي صلاحية (يمنحها مدير الفرع لاحقاً يدوياً)
        $supervisorPhones = ['0799999999', '0797777777', '0999999999'];
        foreach ($supervisorPhones as $phone) {
            $supervisor = User::where('phone', $phone)->first();
            if ($supervisor) {
                $supervisor->assignRole($supervisorRole);
                // ملاحظة: بدون givePermissionTo — المشرف يبدأ بصفر صلاحيات حسب القرار المعتمد
            }
        }

        $this->command->info('✅ Permissions & roles seeded, and test branch manager granted full permissions.');
    }
}