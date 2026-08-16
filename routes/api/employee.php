<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Employee\AuthController; // افتراض اسم كنترولر اللوجن
use App\Http\Controllers\Api\V1\Employee\ProfileController;
use App\Http\Controllers\Api\V1\Employee\TaskController;
use App\Http\Controllers\Api\V1\Employee\LeaveRequestController;
use App\Http\Controllers\Api\V1\Employee\ComplaintController;
use App\Http\Controllers\Api\V1\Employee\AttendanceController;
use App\Http\Controllers\Api\V1\Employee\DashboardController;
use App\Http\Controllers\Api\V1\Employee\PayrollController;
use App\Http\Controllers\Api\V1\Employee\ResignationController;
use App\Http\Controllers\Api\V1\Employee\AnnouncementController;
use App\Http\Controllers\Api\V1\Employee\ExceptionRequestController;
use App\Http\Controllers\Api\V1\Employee\WorkshopController;
use App\Http\Controllers\Api\V1\Employee\EvaluationController;
use App\Http\Controllers\Api\V1\Employee\NotificationController;

/*
|--------------------------------------------------------------------------
| Employee API Routes
|--------------------------------------------------------------------------
| Prefix: api/v1/employees (تمت إضافتها تلقائياً من bootstrap/app.php)
| Middleware: auth:sanctum (يتم تطبيقه على المجموعة ما عدا اللوجن)
|--------------------------------------------------------------------------
*/

// ==========================================
// Epic 0: Authentication (بدون حماية)
// ==========================================
// Route::post('login', [AuthController::class, 'login']);


// ==========================================
// المجموعات المحمية (تتطلب Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // Epic 1: Profile (الملف الشخصي)
    // ==========================================
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile', [ProfileController::class, 'update']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);
    Route::post('change-phone', [ProfileController::class, 'changePhone']); 
    // Route::post('logout', [ProfileController::class, 'logout']);

    // ==========================================
    // Dashboard (لوحة التحكم)
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ==========================================
    // Epic 2: Tasks (المهام)
    // ==========================================
    Route::get('tasks', [TaskController::class, 'index']);
    Route::get('/tasks/home', [TaskController::class, 'home']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::patch('/tasks/{id}/start', [TaskController::class, 'start']);
    Route::patch('/tasks/{id}/complete', [TaskController::class, 'complete']);

    // ==========================================
    // Epic 3: Leave Requests (طلبات الإجازات) - مكتمل
        // ==========================================
    Route::get('leave-requests/balance', [LeaveRequestController::class, 'balance']);
    Route::get('leave-requests/form-data', [LeaveRequestController::class, 'formData']);
    Route::post('leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('leave-requests', [LeaveRequestController::class, 'index']);
    Route::put('leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel']);
    Route::get('leave-requests/{id}', [LeaveRequestController::class, 'show']); // ⚠️ هذا يجب أن يكون آخر واحد دائماً    // ==========================================
    // Epic 4: Complaints (الشكاوى) - قيد التنفيذ
    // ==========================================

    Route::prefix('complaints')->controller(ComplaintController::class)->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
    });

    // ==========================================
    // Announcements Routes (الإعلانات)
    // ==========================================
    Route::get('announcements', [AnnouncementController::class, 'index']);
    Route::patch('announcements/{id}/mark-read', [AnnouncementController::class, 'markRead']); // فوق {id}
    Route::get('announcements/{id}', [AnnouncementController::class, 'show']);
    // ==========================================
    // Epic 5: Exception Requests (طلبات الاستثناء) - قيد التنفيذ
    // ==========================================
    Route::prefix('exception-requests')->group(function () {
        Route::get('exception-requests/form-data', [ExceptionRequestController::class, 'formData']);
        Route::get('/', [ExceptionRequestController::class, 'index']);
        Route::post('/', [ExceptionRequestController::class, 'store']);
        Route::get('/{id}', [ExceptionRequestController::class, 'show']);
        Route::patch('/{id}/cancel', [ExceptionRequestController::class, 'cancel']);
    });

    // ==========================================
    // Epic 6: Evaluations (التقييمات) - قيد التنفيذ
    // ==========================================

    Route::prefix('evaluations')->group(function () {
        Route::get('/', [EvaluationController::class, 'index']);
        Route::get('/{id}', [EvaluationController::class, 'show']);
        Route::patch('/{id}/mark-read', [EvaluationController::class, 'markRead']);
    });
    // ==========================================
    // Epic 7: Workshops (ورش العمل) - قيد التنفيذ
    // ==========================================

    Route::prefix('workshops')->group(function () {
    Route::get('/workshops', [WorkshopController::class, 'index']);
    Route::post('/workshops/{id}/register', [WorkshopController::class, 'register']);
    Route::post('/workshops/{id}/cancel', [WorkshopController::class, 'cancel']);
    });

 
    // ==========================================
    // Epic 9: Payroll (كشف الراتب)  
    // ==========================================
    Route::get('payrolls', [PayrollController::class, 'index']);
    Route::get('payrolls/{id}', [PayrollController::class, 'show']);
    Route::get('payrolls/{id}/pdf', [PayrollController::class, 'pdf']);
    // ==========================================
    // Epic 10: Attendance (الحضور والانصراف) 
    // ==========================================
    Route::get('attendance/today', [AttendanceController::class, 'today']);
    Route::get('attendance/history', [AttendanceController::class, 'getAttendanceHistory']);
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance/summary', [AttendanceController::class, 'summary']);


    // ==========================================
    // Resignations Routes (طلبات الاستقالة)
    // ==========================================

    Route::post('resignations', [ResignationController::class, 'store']);
    Route::get('resignations', [ResignationController::class, 'index']);
    Route::patch('resignations/{id}/withdraw', [ResignationController::class, 'withdraw']); // فوق {id}
    Route::get('resignations/{id}', [ResignationController::class, 'show']);
    
    // ==========================================
    // Epic 11: Notifications (الإشعارات) - قيد التنفيذ
    // ==========================================

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllRead']); // فوق الـ {id}
        Route::patch('/{id}/mark-read', [NotificationController::class, 'markRead']);
    });    
 
    
});