<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pending Dues Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h2 { margin-bottom: 4px; color: #dc2626; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { background-color: #f1f5f9; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Niwasthal — Pending Dues Report</h2>
    <div class="meta">Generated: {{ now()->format('d M Y, h:i A') }} | Total Outstanding: Rs. {{ number_format($summary['totalDues'], 2) }}</div>
    <table>
        <thead>
            <tr><th>Student</th><th>Mobile</th><th>Room</th><th>Period</th><th class="text-right">Outstanding</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($pendingRecords as $r)
            <tr>
                <td>{{ $r->student_name }}</td>
                <td>{{ $r->student?->mobile ?? '-' }}</td>
                <td>{{ $r->room_number ?? '-' }}</td>
                <td>{{ date('M', mktime(0,0,0,$r->month,1)) }} {{ $r->year }}</td>
                <td class="text-right">{{ number_format($r->outstanding, 2) }}</td>
                <td>{{ ucfirst($r->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
