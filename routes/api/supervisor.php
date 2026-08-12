<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\QrCodeController;
use App\Http\Controllers\Api\V1\Supervisor\DashboardController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeController;
use App\Http\Controllers\Api\V1\Supervisor\AttendanceController;
use App\Http\Controllers\Api\V1\Supervisor\TaskController;

// كل الـ routes هون محمية أصلاً بـ auth:sanctum من bootstrap/app.php
// (middleware('auth:sanctum') الداخلية هون صارت غير ضرورية، بس تركها ما بيأذي)

Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::get('/employees', [EmployeeController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::get('/employees/{id}', [EmployeeController::class, 'show']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::get('/employees/{id}/documents', [EmployeeController::class, 'getDocuments']);
Route::delete('/employees/{employee_id}/documents/{document_id}', [EmployeeController::class, 'deleteDocument']);

Route::post('/attendances/manual', [AttendanceController::class, 'manualRecord']);
Route::get('/attendances', [AttendanceController::class, 'index']);
Route::put('/attendances/{id}', [AttendanceController::class, 'update']);