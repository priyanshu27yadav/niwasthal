<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rent Collection Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h2 { margin-bottom: 4px; color: #4f46e5; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Niwasthal — Rent Collection Report</h2>
    <div class="meta">Period: {{ $monthName }} {{ $summary['year'] }} | Generated: {{ now()->format('d M Y, h:i A') }}</div>
    <p><strong>Total Billed:</strong> Rs. {{ number_format($summary['totalBilled'], 2) }} | <strong>Collected:</strong> Rs. {{ number_format($summary['totalCollected'], 2) }} | <strong>Pending:</strong> Rs. {{ number_format($summary['totalPending'], 2) }}</p>
    <table>
        <thead>
            <tr><th>Student</th><th>Room</th><th class="text-right">Total</th><th class="text-right">Paid</th><th class="text-right">Outstanding</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($records as $r)
            <tr>
                <td>{{ $r->student_name }}</td>
                <td>{{ $r->room_number ?? '-' }}</td>
                <td class="text-right">{{ number_format($r->total_amount, 2) }}</td>
                <td class="text-right">{{ number_format($r->paid_amount, 2) }}</td>
                <td class="text-right">{{ number_format($r->outstanding, 2) }}</td>
                <td>{{ ucfirst($r->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
