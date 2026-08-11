<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: right; }
        th { background-color: #f0f0f0; }
        .header { text-align: center; margin-bottom: 20px; }
        .totals { margin-top: 20px; font-weight: bold; }
        .net-salary { font-size: 18px; color: #2c7a2c; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>كشف راتب</h2>
        <p>{{ $employee->profile->full_name ?? '' }} — {{ $employee->employeeDetail->job_title ?? '' }}</p>
        <p>{{ $employee->employeeDetail->department->name ?? '' }} — {{ $employee->employeeDetail->department->branch->name ?? '' }}</p>
        <p>الشهر: {{ \Carbon\Carbon::create($payroll->period->year, $payroll->period->month, 1)->format('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>البند</th>
                <th>النوع</th>
                <th>المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payroll->details as $detail)
            <tr>
                <td>{{ $detail->name }}</td>
                <td>{{ $detail->component_type }}</td>
                <td>{{ number_format($detail->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>إجمالي الإضافات: {{ number_format($payroll->total_bonuses, 2) }}</p>
        <p>إجمالي الخصومات: {{ number_format($payroll->total_deductions, 2) }}</p>
    </div>

    <div class="net-salary">
        الراتب الصافي: {{ number_format($payroll->net_salary, 2) }}
    </div>

    <p>حالة الدفع: {{ $payroll->status === 'paid' ? 'تم الدفع بتاريخ ' . \Carbon\Carbon::parse($payroll->paid_at)->format('Y-m-d') : 'معتمد، بانتظار الدفع' }}</p>
</body>
</html>