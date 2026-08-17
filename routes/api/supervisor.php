<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeTaskController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeLeaveController;
use App\Http\Controllers\Api\V1\Supervisor\TaskController;
use App\Http\Controllers\Api\V1\Supervisor\ProfileController;
use App\Http\Controllers\Api\V1\Supervisor\QrCodeController;
use App\Http\Controllers\Api\V1\Supervisor\LeaveRequestController;
use App\Http\Controllers\Api\V1\Supervisor\AttendanceManagementController;
use App\Http\Controllers\Api\V1\Supervisor\ExceptionController;
use App\Http\Controllers\Api\V1\Employee\WorkshopController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeDocumentController;

Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/employees/{id}', [EmployeeController::class, 'show']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::get('/employees/{id}/attendance', [EmployeeController::class, 'attendance']);
Route::get('/employees/{id}/tasks', [EmployeeTaskController::class, 'index']);
Route::get('/employees/{id}/leaves', [EmployeeLeaveController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);

Route::get('/employees/{employeeId}/documents', [EmployeeDocumentController::class, 'index']);
Route::post('/employees/{employeeId}/documents', [EmployeeDocumentController::class, 'store']);
Route::get('/employees/{employeeId}/documents/{documentId}/download', [EmployeeDocumentController::class, 'download']);
Route::delete('/employees/{employeeId}/documents/{documentId}', [EmployeeDocumentController::class, 'destroy']);



Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::put('/tasks/{id}', [TaskController::class, 'update']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

Route::get('/profile', [ProfileController::class, 'show']);
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);

Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);
Route::get('/employees/{id}/attendance', [EmployeeController::class, 'attendance']);

Route::get('/leaves', [LeaveRequestController::class, 'index']);
Route::get('/leaves/{id}', [LeaveRequestController::class, 'show']);
Route::post('/leaves/{id}/approve', [LeaveRequestController::class, 'approve']);
Route::post('/leaves/{id}/reject', [LeaveRequestController::class, 'reject']);

Route::get('/attendance', [AttendanceManagementController::class, 'index']);
Route::put('/attendance/{id}', [AttendanceManagementController::class, 'update']);
Route::post('/attendance/manual', [AttendanceManagementController::class, 'storeManual']);

Route::get('/exceptions', [ExceptionController::class, 'index']);
Route::get('/exceptions/{id}', [ExceptionController::class, 'show']);
Route::post('/exceptions/{id}/forward-to-owner', [ExceptionController::class, 'forwardToOwner']);
Route::post('/exceptions/{id}/reject', [ExceptionController::class, 'reject']);

Route::get('/workshops', [WorkshopController::class, 'index']);
Route::post('/workshops/{id}/register', [WorkshopController::class, 'register']);
Route::post('/workshops/{id}/cancel', [WorkshopController::class, 'cancel']);

Route::get('/employees/{employeeId}/documents', [EmployeeDocumentController::class, 'index']);
Route::post('/employees/{employeeId}/documents', [EmployeeDocumentController::class, 'store']);
Route::get('/employees/{employeeId}/documents/{documentId}/download', [EmployeeDocumentController::class, 'download']);
Route::delete('/employees/{employeeId}/documents/{documentId}', [EmployeeDocumentController::class, 'destroy']);