<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
        th { background-color: #eee; }
    </style>
</head>
<body>
    <h3>كشف راتب {{ $period->month }}/{{ $period->year }} — الحالة: {{ $period->status }}</h3>
    <table>
        <thead>
            <tr>
                <th>الموظف</th>
                <th>الراتب الأساسي</th>
                <th>الخصومات</th>
                <th>الإضافات</th>
                <th>الصافي</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $row)
            <tr>
                <td>{{ $row->employee_name }}</td>
                <td>{{ $row->gross_salary }}</td>
                <td>{{ $row->total_deductions }}</td>
                <td>{{ $row->total_bonuses }}</td>
                <td>{{ $row->net_salary }}</td>
                <td>{{ $row->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
