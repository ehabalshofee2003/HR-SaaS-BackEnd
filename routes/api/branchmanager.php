<?php

use Illuminate\Support\Facades\Route;
use  App\Http\Controllers\Api\V1\BranchManager\DepartmentController;
use  App\Http\Controllers\Api\V1\BranchManager\SupervisorController;
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

// branchmanager routes

Route::post('/departments/{id}/assign-supervisor', [DepartmentController::class, 'assignSupervisor']);

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/departments/{id}', [DepartmentController::class, 'show']);
    Route::put('/departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy']);
    Route::post('/departments/{id}/toggle-status', [DepartmentController::class, 'toggleStatus']);
});

Route::get('/supervisors', [SupervisorController::class, 'index']);
Route::post('/supervisors', [SupervisorController::class, 'store']);
Route::get('/supervisors/{id}', [SupervisorController::class, 'show']);
Route::put('/supervisors/{id}', [SupervisorController::class, 'update']);
Route::delete('/supervisors/{id}', [SupervisorController::class, 'destroy']);
Route::post('/supervisors/{id}/toggle-status', [SupervisorController::class, 'toggleStatus']);
Route::post('/supervisors/{id}/reset-password', [SupervisorController::class, 'resetPassword']);

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
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
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance/manual', [AttendanceController::class, 'storeManual']);
    Route::put('/attendance/{id}', [AttendanceController::class, 'update']);
    Route::get('/attendance/export', [AttendanceController::class, 'export']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/evaluations', [EvaluationController::class, 'index']);
    Route::post('/evaluations', [EvaluationController::class, 'store']);
    Route::get('/evaluations/{id}', [EvaluationController::class, 'show']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::get('/leaves/{id}', [LeaveController::class, 'show']);
    Route::post('/leaves/{id}/approve', [LeaveController::class, 'approve']);
    Route::post('/leaves/{id}/reject', [LeaveController::class, 'reject']);
    Route::get('/employees/{employee_user_id}/leave-balances', [LeaveController::class, 'balances']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/exception-requests', [ExceptionRequestController::class, 'index']);
    Route::get('/exception-requests/{id}', [ExceptionRequestController::class, 'show']);
    Route::post('/exception-requests/{id}/forward-to-owner', [ExceptionRequestController::class, 'forwardToOwner']);
    Route::post('/exception-requests/{id}/reject', [ExceptionRequestController::class, 'reject']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/payrolls', [PayrollController::class, 'index']);
    Route::post('/payrolls/calculate', [PayrollController::class, 'calculate']);
    Route::get('/payrolls/{id}', [PayrollController::class, 'show']);
    Route::put('/payrolls/{id}/entries/{employee_user_id}', [PayrollController::class, 'updateEntry']);
    Route::post('/payrolls/{id}/entries/{employee_user_id}/exceptions', [PayrollController::class, 'addException']);
    Route::post('/payrolls/{id}/approve', [PayrollController::class, 'approve']);
    Route::post('/payrolls/{id}/mark-as-paid', [PayrollController::class, 'markAsPaid']);
    Route::get('/payrolls/{id}/export-pdf', [PayrollController::class, 'exportPdf']);
    Route::get('/payrolls/{id}/export-excel', [PayrollController::class, 'exportExcel']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::get('/complaints/{id}', [ComplaintController::class, 'show']);
    Route::post('/complaints/{id}/reply', [ComplaintController::class, 'reply']);
    Route::post('/complaints/{id}/escalate', [ComplaintController::class, 'escalate']);
    Route::post('/complaints/{id}/resolve', [ComplaintController::class, 'resolve']);
    Route::post('/complaints/{id}/reject', [ComplaintController::class, 'reject']);
});

Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/resignations', [ResignationController::class, 'index']);
    Route::get('/resignations/{id}', [ResignationController::class, 'show']);
    Route::post('/resignations/{id}/accept', [ResignationController::class, 'accept']);
    Route::post('/resignations/{id}/reject', [ResignationController::class, 'reject']);
});


Route::prefix('branch-manager')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [AccountController::class, 'profile']);
    Route::put('/profile', [AccountController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AccountController::class, 'changePassword']);
    Route::get('/settings', [AccountController::class, 'settings']);
    Route::put('/settings', [AccountController::class, 'updateSettings']);
    Route::get('/branch-data', [AccountController::class, 'branchData']);
    Route::put('/branch-data', [AccountController::class, 'updateBranchData']);
});