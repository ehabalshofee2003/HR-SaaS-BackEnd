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
    <h3>تقرير الحضور</h3>
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>الموظف</th>
                <th>القسم</th>
                <th>وقت الدخول</th>
                <th>وقت الخروج</th>
                <th>ساعات العمل</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row->check_in)->format('Y-m-d') }}</td>
                <td>{{ $row->employee_name }}</td>
                <td>{{ $row->department_name }}</td>
                <td>{{ \Carbon\Carbon::parse($row->check_in)->format('H:i') }}</td>
                <td>{{ $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('H:i') : '-' }}</td>
                <td>{{ $row->work_hours }}</td>
                <td>{{ $row->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
