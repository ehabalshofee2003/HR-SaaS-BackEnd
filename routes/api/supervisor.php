<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeTaskController;
use App\Http\Controllers\Api\V1\Supervisor\EmployeeLeaveController;
use App\Http\Controllers\Api\V1\Supervisor\TaskController;
use App\Http\Controllers\Api\V1\Supervisor\ProfileController;

Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/employees/{id}', [EmployeeController::class, 'show']);
Route::put('/employees/{id}', [EmployeeController::class, 'update']);
Route::get('/employees/{id}/attendance', [EmployeeController::class, 'attendance']);
Route::get('/employees/{id}/tasks', [EmployeeTaskController::class, 'index']);
Route::get('/employees/{id}/leaves', [EmployeeLeaveController::class, 'index']);


Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::put('/tasks/{id}', [TaskController::class, 'update']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);

Route::get('/profile', [ProfileController::class, 'show']);
Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);