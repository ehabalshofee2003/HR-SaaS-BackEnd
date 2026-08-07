<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\QrCodeController;
use App\Http\Controllers\Api\V1\Supervisor\DashboardController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeController;
use App\Http\Controllers\Api\V1\Supervisor\AttendanceController;
use App\Http\Controllers\Api\V1\Supervisor\TaskController;
 
// supervisor routes

Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);



Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index']);

    
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    // المستندات (Documents)
    Route::get('/employees/{id}/documents', [EmployeeController::class, 'getDocuments']);
    Route::delete('/employees/{employee_id}/documents/{document_id}', [EmployeeController::class, 'deleteDocument']);
        
    // الحضور (Attendance)
    Route::post('/attendances/manual', [AttendanceController::class, 'manualRecord']);
    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::put('/attendances/{id}', [AttendanceController::class, 'update']);


    // Route::middleware('auth:sanctum')->group(function () {
    //     Route::get('/tasks', [TaskController::class, 'index']);
    //     Route::post('/tasks', [TaskController::class, 'store']);
    //     Route::get('/tasks/{id}', [TaskController::class, 'show']);
    //     Route::put('/tasks/{id}', [TaskController::class, 'update']);
    //     Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    // });

});