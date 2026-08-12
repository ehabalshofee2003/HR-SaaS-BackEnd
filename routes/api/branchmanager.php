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

// كل الـ routes هون محمية أصلاً ومسبوقة بـ /api/v1/branch-manager
// من bootstrap/app.php — ما في داعي لأي prefix أو middleware إضافي هون.

Route::post('/departments/{id}/assign-supervisor', [DepartmentController::class, 'assignSupervisor']);
Route::get('/departments', [DepartmentController::class, 'index']);
Route::post('/departments', [DepartmentController::class, 'store']);
Route::get('/departments/{id}', [DepartmentController::class, 'show']);
Route::put('/departments/{id}', [DepartmentController::class, 'update']);
Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
Route::post('/departments/{id}/toggle-status', [DepartmentController::class, 'toggleStatus']);

Route::get('/supervisors', [SupervisorController::class, 'index']);
Route::post('/supervisors', [SupervisorController::class, 'store']);
Route::get('/supervisors/{id}', [SupervisorController::class, 'show']);
Route::put('/supervisors/{id}', [SupervisorController::class, 'update']);
Route::delete('/supervisors/{id}', [SupervisorController::class, 'destroy']);
Route::post('/supervisors/{id}/toggle-status', [SupervisorController::class, 'toggleStatus']);
Route::post('/supervisors/{id}/reset-password', [SupervisorController::class, 'resetPassword']);

Route::get('/employees', [EmployeeController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::get('/employees/{id}', [EmployeeController::class, 'show']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
Route::post('/employees/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
Route::post('/employees/{id}/reset-password', [EmployeeController::class, 'resetPassword']);
Route::get('/employees/{id}/documents', [EmployeeController::class, 'documents']);
Route::post('/employees/{id}/documents', [EmployeeController::class, 'uploadDocument']);
Route::delete('/employees/{employee_id}/documents/{document_id}', [EmployeeController::class, 'deleteDocument']);

Route::get('/attendance', [AttendanceController::class, 'index']);
Route::post('/attendance/manual', [AttendanceController::class, 'storeManual']);
Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
Route::get('/attendance/export', [AttendanceController::class, 'export']);

Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::get('/tasks/{id}', [TaskController::class, 'show']);
Route::put('/tasks/{id}', [TaskController::class, 'update']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

Route::get('/evaluations', [EvaluationController::class, 'index']);
Route::post('/evaluations', [EvaluationController::class, 'store']);
Route::get('/evaluations/{id}', [EvaluationController::class, 'show']);

Route::get('/leaves', [LeaveController::class, 'index']);
Route::get('/leaves/{id}', [LeaveController::class, 'show']);
Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve']);
Route::post('/leaves/{id}/reject', [LeaveController::class, 'reject']);
Route::get('/employees/{employee_user_id}/leave-balances', [LeaveController::class, 'balances']);

Route::get('/exception-requests', [ExceptionRequestController::class, 'index']);
Route::get('/exception-requests/{id}', [ExceptionRequestController::class, 'show']);
Route::post('/exception-requests/{id}/forward-to-owner', [ExceptionRequestController::class, 'forwardToOwner']);
Route::post('/exception-requests/{id}/reject', [ExceptionRequestController::class, 'reject']);

Route::get('/payrolls', [PayrollController::class, 'index']);
Route::post('/payrolls/calculate', [PayrollController::class, 'calculate']);
Route::get('/payrolls/{id}', [PayrollController::class, 'show']);
Route::put('/payrolls/{id}/entries/{employee_user_id}', [PayrollController::class, 'updateEntry']);
Route::post('/payrolls/{id}/entries/{employee_user_id}/exceptions', [PayrollController::class, 'addException']);
Route::post('/payrolls/{id}/approve', [PayrollController::class, 'approve']);
Route::post('/payrolls/{id}/mark-as-paid', [PayrollController::class, 'markAsPaid']);
Route::get('/payrolls/{id}/export-pdf', [PayrollController::class, 'exportPdf']);
Route::get('/payrolls/{id}/export-excel', [PayrollController::class, 'exportExcel']);

Route::get('/complaints', [ComplaintController::class, 'index']);
Route::get('/complaints/{id}', [ComplaintController::class, 'show']);
Route::post('/complaints/{id}/reply', [ComplaintController::class, 'reply']);
Route::post('/complaints/{id}/escalate', [ComplaintController::class, 'escalate']);
Route::post('/complaints/{id}/resolve', [ComplaintController::class, 'resolve']);
Route::post('/complaints/{id}/reject', [ComplaintController::class, 'reject']);

Route::get('/resignations', [ResignationController::class, 'index']);
Route::get('/resignations/{id}', [ResignationController::class, 'show']);
Route::post('/resignations/{id}/accept', [ResignationController::class, 'accept']);
Route::post('/resignations/{id}/reject', [ResignationController::class, 'reject']);

Route::get('/profile', [AccountController::class, 'profile']);
Route::put('/profile', [AccountController::class, 'updateProfile']);
Route::post('/profile/change-password', [AccountController::class, 'changePassword']);
Route::get('/settings', [AccountController::class, 'settings']);
Route::put('/settings', [AccountController::class, 'updateSettings']);
Route::get('/branch-data', [AccountController::class, 'branchData']);
Route::put('/branch-data', [AccountController::class, 'updateBranchData']);

Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::post('/announcements', [AnnouncementController::class, 'store']);
Route::get('/announcements/{id}/readers', [AnnouncementController::class, 'readers']);
Route::post('/announcements/{id}/archive', [AnnouncementController::class, 'archive']);
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

Route::get('/workshops', [WorkshopController::class, 'index']);
Route::post('/workshops', [WorkshopController::class, 'store']);
Route::get('/workshops/{id}', [WorkshopController::class, 'show']);
Route::put('/workshops/{id}', [WorkshopController::class, 'update']);
Route::post('/workshops/{id}/cancel', [WorkshopController::class, 'cancel']);
Route::get('/workshops/{id}/attendees', [WorkshopController::class, 'attendees']);
Route::post('/workshops/{id}/attendees/{user_id}/attend', [WorkshopController::class, 'markAttendance']);

Route::get('/reports/attendance', [ReportController::class, 'attendance']);
Route::get('/reports/tasks', [ReportController::class, 'tasks']);
Route::get('/reports/financial', [ReportController::class, 'financial']);
Route::get('/reports/performance', [ReportController::class, 'performance']);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/permissions/assignable', [PermissionController::class, 'assignableCatalog'])
    ->middleware('permission:supervisors.assign');

Route::get('/supervisors/{id}/permissions', [PermissionController::class, 'supervisorPermissions'])
    ->middleware('permission:supervisors.assign');

Route::put('/supervisors/{id}/permissions', [PermissionController::class, 'updateSupervisorPermissions'])
    ->middleware('permission:supervisors.assign');