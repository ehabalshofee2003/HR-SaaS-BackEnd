<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Owner\PermissionController;
use App\Http\Controllers\Api\V1\Owner\BranchController;
use App\Http\Controllers\Api\V1\Owner\ManagerController;
use App\Http\Controllers\Api\V1\Owner\DashboardController;
use App\Http\Controllers\Api\V1\Owner\ExceptionController;
use App\Http\Controllers\Api\V1\Owner\AttendanceReportController;
use App\Http\Controllers\Api\V1\Owner\PayrollReportController;
use App\Http\Controllers\Api\V1\Owner\FinancialReportController;
use App\Http\Controllers\Api\V1\Owner\BranchComparisonReportController;
use App\Http\Controllers\Api\V1\Owner\PerformanceReportController;
use App\Http\Controllers\Api\V1\Owner\ProfileController;
use App\Http\Controllers\Api\V1\Owner\NotificationController;

Route::get('/permissions/assignable', [PermissionController::class, 'assignableCatalog']);
Route::get('/managers/{id}/permissions', [PermissionController::class, 'managerPermissions']);
Route::put('/managers/{id}/permissions', [PermissionController::class, 'updateManagerPermissions']);


Route::get('/branches', [BranchController::class, 'index']);
Route::post('/branches', [BranchController::class, 'store']);
Route::get('/branches/{id}', [BranchController::class, 'show']);
Route::put('/branches/{id}', [BranchController::class, 'update']);
Route::delete('/branches/{id}', [BranchController::class, 'destroy']);

Route::apiResource('managers', ManagerController::class);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/exceptions/pending-count', [ExceptionController::class, 'pendingCount']);
Route::apiResource('exceptions', ExceptionController::class)->only(['index', 'show']);
Route::post('/exceptions/{id}/decide', [ExceptionController::class, 'decide']);


Route::get('/reports/attendance', [AttendanceReportController::class, 'index']);
Route::get('/reports/attendance/export/pdf', [AttendanceReportController::class, 'exportPdf']);
Route::get('/reports/attendance/export/excel', [AttendanceReportController::class, 'exportExcel']);

Route::get('/reports/payroll', [PayrollReportController::class, 'index']);
Route::get('/reports/payroll/export/pdf', [PayrollReportController::class, 'exportPdf']);
Route::get('/reports/payroll/export/excel', [PayrollReportController::class, 'exportExcel']);

Route::get('/reports/financial', [FinancialReportController::class, 'index']);
Route::get('/reports/financial/export/pdf', [FinancialReportController::class, 'exportPdf']);
Route::get('/reports/financial/export/excel', [FinancialReportController::class, 'exportExcel']);


Route::get('/reports/branch-comparison', [BranchComparisonReportController::class, 'index']);
Route::get('/reports/branch-comparison/export/pdf', [BranchComparisonReportController::class, 'exportPdf']);
Route::get('/reports/branch-comparison/export/excel', [BranchComparisonReportController::class, 'exportExcel']);


Route::get('/reports/performance', [PerformanceReportController::class, 'index']);
Route::get('/reports/performance/export/pdf', [PerformanceReportController::class, 'exportPdf']);
Route::get('/reports/performance/export/excel', [PerformanceReportController::class, 'exportExcel']);

Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);

Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);