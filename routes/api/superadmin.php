<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SuperAdmin\AuthController;
use App\Http\Controllers\Api\V1\SuperAdmin\DashboardController;
use App\Http\Controllers\Api\V1\SuperAdmin\CompanyController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/companies/export/excel', [CompanyController::class, 'exportExcel']);
    Route::get('/companies/export/pdf', [CompanyController::class, 'exportPdf']);
    Route::apiResource('companies', CompanyController::class)->except(['destroy']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    Route::post('/companies/{id}/suspend', [CompanyController::class, 'suspend']);
    Route::post('/companies/{id}/activate', [CompanyController::class, 'activate']);
});