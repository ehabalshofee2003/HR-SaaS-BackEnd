<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\BranchManager\DepartmentController;
use App\Http\Controllers\Api\V1\BranchManager\SupervisorController;
use App\Http\Controllers\Api\V1\BranchManager\EmployeeController;
use App\Http\Controllers\Api\V1\BranchManager\AttendanceController;
use App\Http\Controllers\Api\V1\BranchManager\TaskController;
use App\Http\Controllers\Api\V1\BranchManager\EvaluationController;
use App\Http\Controllers\Api\V1\BranchManager\LeaveController;
use App\Http\Controllers\Api\V1\BranchManager\ExceptionRequestController;
use App\Http\Controllers\Api\V1\BranchManager\PayrollController;
use App\Http\Controllers\Api\V1\BranchManager\ComplaintController;
use App\Http\Controllers\Api\V1\BranchManager\ResignationController;
use App\Http\Controllers\Api\V1\BranchManager\AccountController;
use App\Http\Controllers\Api\V1\BranchManager\AnnouncementController;
use App\Http\Controllers\Api\V1\BranchManager\WorkshopController;
use App\Http\Controllers\Api\V1\BranchManager\ReportController;
use App\Http\Controllers\Api\V1\BranchManager\NotificationController;
use App\Http\Controllers\Api\V1\BranchManager\DashboardController;
use App\Http\Controllers\Api\V1\BranchManager\PermissionController;

// كل الروابط هون محمية أصلاً بـ auth:sanctum ومسبوقة بـ /api/v1/branch-manager
// من bootstrap/app.php. الإضافة هلق: middleware('permission:xxx') لكل route
// حسب الصلاحية المطلوبة فعليًا لتنفيذه.

// ===================== Departments =====================
Route::get('/departments', [DepartmentController::class, 'index'])->middleware('permission:departments.view');
Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:departments.create');
Route::get('/departments/{id}', [DepartmentController::class, 'show'])->middleware('permission:departments.view');
Route::put('/departments/{id}', [DepartmentController::class, 'update'])->middleware('permission:departments.update');
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->middleware('permission:departments.delete');
Route::post('/departments/{id}/toggle-status', [DepartmentController::class, 'toggleStatus'])->middleware('permission:departments.update');
Route::post('/departments/{id}/assign-supervisor', [DepartmentController::class, 'assignSupervisor'])->middleware('permission:supervisors.assign');

// ===================== Supervisors =====================
Route::get('/supervisors', [SupervisorController::class, 'index'])->middleware('permission:supervisors.view');
Route::post('/supervisors', [SupervisorController::class, 'store'])->middleware('permission:supervisors.create');
Route::get('/supervisors/{id}', [SupervisorController::class, 'show'])->middleware('permission:supervisors.view');
Route::put('/supervisors/{id}', [SupervisorController::class, 'update'])->middleware('permission:supervisors.update');
Route::delete('/supervisors/{id}', [SupervisorController::class, 'destroy'])->middleware('permission:supervisors.delete');
Route::post('/supervisors/{id}/toggle-status', [SupervisorController::class, 'toggleStatus'])->middleware('permission:supervisors.update');
Route::post('/supervisors/{id}/reset-password', [SupervisorController::class, 'resetPassword'])->middleware('permission:supervisors.update');

// ===================== Supervisor Permissions (Delegation) =====================
Route::get('/permissions/assignable', [PermissionController::class, 'assignableCatalog'])->middleware('permission:supervisors.assign');
Route::get('/supervisors/{id}/permissions', [PermissionController::class, 'supervisorPermissions'])->middleware('permission:supervisors.assign');
Route::put('/supervisors/{id}/permissions', [PermissionController::class, 'updateSupervisorPermissions'])->middleware('permission:supervisors.assign');

// ===================== Employees =====================
Route::get('/employees', [EmployeeController::class, 'index'])->middleware('permission:employees.view');
Route::post('/employees', [EmployeeController::class, 'store'])->middleware('permission:employees.create');
Route::get('/employees/{id}', [EmployeeController::class, 'show'])->middleware('permission:employees.view');
Route::put('/employees/{id}', [EmployeeController::class, 'update'])->middleware('permission:employees.update');
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->middleware('permission:employees.delete');
Route::post('/employees/{id}/toggle-status', [EmployeeController::class, 'toggleStatus'])->middleware('permission:employees.update');
Route::post('/employees/{id}/reset-password', [EmployeeController::class, 'resetPassword'])->middleware('permission:employees.update');
Route::get('/employees/{id}/documents', [EmployeeController::class, 'documents'])->middleware('permission:employees.view');
Route::post('/employees/{id}/documents', [EmployeeController::class, 'uploadDocument'])->middleware('permission:employees.documents.manage');
Route::delete('/employees/{employee_id}/documents/{document_id}', [EmployeeController::class, 'deleteDocument'])->middleware('permission:employees.documents.manage');

// ===================== Attendance =====================
Route::get('/attendance', [AttendanceController::class, 'index'])->middleware('permission:attendance.view');
Route::post('/attendance/manual', [AttendanceController::class, 'storeManual'])->middleware('permission:attendance.manual_entry');
Route::put('/attendance/{id}', [AttendanceController::class, 'update'])->middleware('permission:attendance.manual_entry');
Route::get('/attendance/export', [AttendanceController::class, 'export'])->middleware('permission:attendance.export');

// ===================== Tasks =====================
Route::get('/tasks', [TaskController::class, 'index'])->middleware('permission:tasks.view');
Route::post('/tasks', [TaskController::class, 'store'])->middleware('permission:tasks.create');
Route::get('/tasks/{id}', [TaskController::class, 'show'])->middleware('permission:tasks.view');
Route::put('/tasks/{id}', [TaskController::class, 'update'])->middleware('permission:tasks.update');
Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->middleware('permission:tasks.delete');

// ===================== Evaluations =====================
Route::get('/evaluations', [EvaluationController::class, 'index'])->middleware('permission:evaluations.view');
Route::post('/evaluations', [EvaluationController::class, 'store'])->middleware('permission:evaluations.create');
Route::get('/evaluations/{id}', [EvaluationController::class, 'show'])->middleware('permission:evaluations.view');

// ===================== Leaves =====================
Route::get('/leaves', [LeaveController::class, 'index'])->middleware('permission:leaves.view');
Route::get('/leaves/{id}', [LeaveController::class, 'show'])->middleware('permission:leaves.view');
Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve'])->middleware('permission:leaves.approve');
Route::post('/leaves/{id}/reject', [LeaveController::class, 'reject'])->middleware('permission:leaves.reject');
Route::get('/employees/{employee_user_id}/leave-balances', [LeaveController::class, 'balances'])->middleware('permission:leaves.view');

// ===================== Exception Requests =====================
Route::get('/exception-requests', [ExceptionRequestController::class, 'index'])->middleware('permission:exceptions.view');
Route::get('/exception-requests/{id}', [ExceptionRequestController::class, 'show'])->middleware('permission:exceptions.view');
Route::post('/exception-requests/{id}/forward-to-owner', [ExceptionRequestController::class, 'forwardToOwner'])->middleware('permission:exceptions.forward');
Route::post('/exception-requests/{id}/reject', [ExceptionRequestController::class, 'reject'])->middleware('permission:exceptions.reject');

// ===================== Payroll =====================
Route::get('/payrolls', [PayrollController::class, 'index'])->middleware('permission:payroll.view');
Route::post('/payrolls/calculate', [PayrollController::class, 'calculate'])->middleware('permission:payroll.calculate');
Route::get('/payrolls/{id}', [PayrollController::class, 'show'])->middleware('permission:payroll.view');
Route::put('/payrolls/{id}/entries/{employee_user_id}', [PayrollController::class, 'updateEntry'])->middleware('permission:payroll.calculate');
Route::post('/payrolls/{id}/entries/{employee_user_id}/exceptions', [PayrollController::class, 'addException'])->middleware('permission:payroll.calculate');
Route::post('/payrolls/{id}/approve', [PayrollController::class, 'approve'])->middleware('permission:payroll.approve');
Route::post('/payrolls/{id}/mark-as-paid', [PayrollController::class, 'markAsPaid'])->middleware('permission:payroll.mark_paid');
Route::get('/payrolls/{id}/export-pdf', [PayrollController::class, 'exportPdf'])->middleware('permission:payroll.export');
Route::get('/payrolls/{id}/export-excel', [PayrollController::class, 'exportExcel'])->middleware('permission:payroll.export');

// ===================== Complaints =====================
Route::get('/complaints', [ComplaintController::class, 'index'])->middleware('permission:complaints.view');
Route::get('/complaints/{id}', [ComplaintController::class, 'show'])->middleware('permission:complaints.view');
Route::post('/complaints/{id}/reply', [ComplaintController::class, 'reply'])->middleware('permission:complaints.respond');
Route::post('/complaints/{id}/escalate', [ComplaintController::class, 'escalate'])->middleware('permission:complaints.escalate');
Route::post('/complaints/{id}/resolve', [ComplaintController::class, 'resolve'])->middleware('permission:complaints.resolve');
Route::post('/complaints/{id}/reject', [ComplaintController::class, 'reject'])->middleware('permission:complaints.resolve');

// ===================== Resignations =====================
Route::get('/resignations', [ResignationController::class, 'index'])->middleware('permission:resignations.view');
Route::get('/resignations/{id}', [ResignationController::class, 'show'])->middleware('permission:resignations.view');
Route::post('/resignations/{id}/accept', [ResignationController::class, 'accept'])->middleware('permission:resignations.approve');
Route::post('/resignations/{id}/reject', [ResignationController::class, 'reject'])->middleware('permission:resignations.reject');

// ===================== Profile & Settings =====================
// ملاحظة: بدون middleware('permission:xxx') — هاي بيانات المستخدم نفسه (self-service)،
// أي مستخدم مسجّل دخول كـ branch-manager يقدر يوصلها بغض النظر عن صلاحياته الأخرى.
Route::get('/profile', [AccountController::class, 'profile']);
Route::put('/profile', [AccountController::class, 'updateProfile']);
Route::post('/profile/change-password', [AccountController::class, 'changePassword']);
Route::get('/settings', [AccountController::class, 'settings'])->middleware('permission:settings.view');
Route::put('/settings', [AccountController::class, 'updateSettings'])->middleware('permission:settings.update');
Route::get('/branch-data', [AccountController::class, 'branchData'])->middleware('permission:settings.view');
Route::put('/branch-data', [AccountController::class, 'updateBranchData'])->middleware('permission:settings.update');

// ===================== Announcements =====================
Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('permission:announcements.view');
Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcements.create');
Route::get('/announcements/{id}/readers', [AnnouncementController::class, 'readers'])->middleware('permission:announcements.view');
Route::post('/announcements/{id}/archive', [AnnouncementController::class, 'archive'])->middleware('permission:announcements.delete');
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcements.delete');

// ===================== Workshops =====================
Route::get('/workshops', [WorkshopController::class, 'index'])->middleware('permission:workshops.view');
Route::post('/workshops', [WorkshopController::class, 'store'])->middleware('permission:workshops.create');
Route::get('/workshops/{id}', [WorkshopController::class, 'show'])->middleware('permission:workshops.view');
Route::put('/workshops/{id}', [WorkshopController::class, 'update'])->middleware('permission:workshops.update');
Route::post('/workshops/{id}/cancel', [WorkshopController::class, 'cancel'])->middleware('permission:workshops.update');
Route::get('/workshops/{id}/attendees', [WorkshopController::class, 'attendees'])->middleware('permission:workshops.view');
Route::post('/workshops/{id}/attendees/{user_id}/attend', [WorkshopController::class, 'markAttendance'])->middleware('permission:workshops.manage_attendance');

// ===================== Reports =====================
Route::get('/reports/attendance', [ReportController::class, 'attendance'])->middleware('permission:reports.view');
Route::get('/reports/tasks', [ReportController::class, 'tasks'])->middleware('permission:reports.view');
Route::get('/reports/financial', [ReportController::class, 'financial'])->middleware('permission:reports.view');
Route::get('/reports/performance', [ReportController::class, 'performance'])->middleware('permission:reports.view');

// ===================== Notifications =====================
// ملاحظة: بدون middleware('permission:xxx') — إشعارات المستخدم نفسه (self-service).
Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

// ===================== Dashboard =====================
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view');