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
Route::post('login', [AuthController::class, 'login']);


// ==========================================
// المجموعات المحمية (تتطلب Token)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // Epic 1: Profile (الملف الشخصي)
    // ==========================================
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

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
    Route::prefix('leave-requests')->controller(LeaveRequestController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{id}', 'show');
    });

    // ==========================================
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
    // Route::post('exception-requests', [ExceptionRequestController::class, 'store']);
    // Route::get('exception-requests', [ExceptionRequestController::class, 'index']);

    // ==========================================
    // Epic 6: Evaluations (التقييمات) - قيد التنفيذ
    // ==========================================
    // Route::get('evaluations', [EvaluationController::class, 'index']);

    // ==========================================
    // Epic 7: Workshops (ورش العمل) - قيد التنفيذ
    // ==========================================
    // Route::get('workshops', [WorkshopController::class, 'index']);
    // Route::post('workshops/{id}/register', [WorkshopController::class, 'register']);

    // ==========================================
    // Epic 8: Support Tickets (تذاكر الدعم) - قيد التنفيذ
    // ==========================================
    // Route::post('support-tickets', [SupportTicketController::class, 'store']);
    // Route::get('support-tickets', [SupportTicketController::class, 'index']);
    // Route::get('support-tickets/{id}', [SupportTicketController::class, 'show']);

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
    Route::get('attendance/history', [AttendanceController::class, 'history']);
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']); // موجود مسبقاً
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);

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
    // Route::get('notifications', [NotificationController::class, 'index']);
    
});