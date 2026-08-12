<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Owner\PermissionController;
use App\Http\Controllers\Api\V1\Owner\BranchController;

Route::get('/permissions/assignable', [PermissionController::class, 'assignableCatalog']);
Route::get('/managers/{id}/permissions', [PermissionController::class, 'managerPermissions']);
Route::put('/managers/{id}/permissions', [PermissionController::class, 'updateManagerPermissions']);


Route::get('/branches', [BranchController::class, 'index']);
Route::post('/branches', [BranchController::class, 'store']);
Route::get('/branches/{id}', [BranchController::class, 'show']);
Route::put('/branches/{id}', [BranchController::class, 'update']);
Route::delete('/branches/{id}', [BranchController::class, 'destroy']);