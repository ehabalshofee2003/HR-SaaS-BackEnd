<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * كل الصلاحيات الممكنة بالنظام لدور مدير الفرع.
     * هاي القائمة تُستخدم فقط لإنشاء الصلاحيات بجدول permissions —
     * ما منربطها بالـ role مباشرة (شوف ملاحظة أسفل method الـ run).
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

    /**
     * كل الصلاحيات الممكنة بالنظام لدور المشرف.
     * نفس الملاحظة: هاي بس مرجع لإنشاء الصلاحيات، مش مصدر صلاحيات فعلية للـ role.
     */
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

        // دمج كل الصلاحيات الفريدة (بدون تكرار) وإنشاؤها دفعة وحدة بجدول permissions
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

        // إنشاء الأدوار الأربعة كـ "تصنيف" فقط
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => $guard]);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);

        // ============================================================
        // ملاحظة تصميمية مهمة — لماذا ما منربط صلاحيات بالـ role مباشرة:
        //
        // ($managerRole->syncPermissions(...) و $supervisorRole->syncPermissions(...) تم حذفهما عمدًا)
        //
        // كل مستخدم (مدير فرع أو مشرف) ياخد صلاحياته الفعلية بشكل مباشر
        // ومستقل (direct permissions عبر جدول model_has_permissions)،
        // وليس موروثة تلقائيًا من الـ role.
        //
        // السبب: هيك الـ Owner يقدر يعدّل أو يسحب صلاحيات مدير فرع معيّن،
        // ومدير الفرع يقدر يعدّل صلاحيات مشرف معيّن، بدون ما يأثر هالتعديل
        // على باقي المستخدمين الآخرين من نفس الدور.
        //
        // لو ربطنا الصلاحيات بالـ role مباشرة، كل مستخدم بنفس الدور كان
        // رح ياخد نفس الصلاحيات تلقائيًا (لأنه صلاحيات Spatie تراكمية فقط،
        // ما فيها طريقة "تسحب" صلاحية موروثة من الـ role لمستخدم واحد بعينه).
        //
        // الـ role هلق بيبقى بس "تصنيف/label" يحدد نوع المستخدم
        // (مفيد لعرض القائمة المناسبة بالواجهة مثلاً) — لا يحمل صلاحيات فعلية.
        //
        // القوائم $branchManagerPermissions و $supervisorPermissions فوق
        // تضل موجودة كمرجع للسقف النظري لكل دور، وهتُستخدم لاحقًا ضمن
        // Owner backend لمنح كل مدير فرع جديد صلاحياته الكاملة تلقائيًا
        // عند إنشاء حسابه (direct grant، مش عبر الـ role).
        // ============================================================
    }
}