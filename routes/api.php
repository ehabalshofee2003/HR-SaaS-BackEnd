 <?php

use Illuminate\Support\Facades\Route;

// سيتم تحميل المسارات من مجلد routes/api/ تلقائياً عبر bootstrap/app.php

// === راوت مؤقت لإنشاء بيانات اختبار الرواتب - احذفه بعد الانتهاء ===
Route::get('/temp-seed-payroll', function () {
    $user = \App\Models\Identity\User::find(1); // ← غير الرقم 1 إلى ID الموظف الذي تم اختباره سابقاً
    if (!$user) return response()->json(['error' => 'User not found'], 404);

    $companyId = $user->getCurrentCompanyId();
    if (!$companyId) return response()->json(['error' => 'User has no company hierarchy'], 400);

    $period = \App\Models\Payroll\PayrollPeriod::firstOrCreate(
        ['company_id' => $companyId, 'month' => now()->month, 'year' => now()->year],
        ['status' => 'approved']
    );

    $record = \App\Models\Payroll\PayrollRecord::firstOrCreate(
        ['employee_user_id' => $user->id, 'period_id' => $period->id],
        [
            'gross_salary' => 1500.00,
            'total_deductions' => 100.00,
            'total_bonuses' => 50.00,
            'net_salary' => 1450.00,
            'status' => 'approved',
            'approved_at' => now()
        ]
    );

    \App\Models\Payroll\PayrollRecordDetail::firstOrCreate(
        ['record_id' => $record->id, 'name' => 'الراتب الأساسي'],
        ['component_type' => 'base_salary', 'amount' => 1500.00]
    );

    return response()->json(['message' => 'Seeded Successfully', 'record_id' => $record->id]);
});
