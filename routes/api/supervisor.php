<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Supervisor\QrCodeController;
// supervisor routes

Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);
