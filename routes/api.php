 <?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\V1\AuthController;

// Route::post('/auth/login', [AuthController::class, 'login']);
// Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    
    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/request-phone-change', [AuthController::class, 'requestPhoneChange']);
    Route::post('/auth/verify-phone-change', [AuthController::class, 'verifyPhoneChange']);
});
    