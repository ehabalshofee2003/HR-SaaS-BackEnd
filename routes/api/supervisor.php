<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\QrCodeController;
use App\Http\Controllers\Api\V1\Supervisor\DashboardController;

// supervisor routes

Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);


Route::get('/dashboard', [DashboardController::class, 'index']);
