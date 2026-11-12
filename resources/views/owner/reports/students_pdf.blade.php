<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h2 { margin-bottom: 4px; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { background-color: #f1f5f9; }
    </style>
</head>
<body>
    <h2>Niwasthal — Student Directory Report</h2>
    <p>Active: {{ $summary['totalActive'] }} | Inactive/Vacated: {{ $summary['totalInactive'] }}</p>
    <table>
        <thead><tr><th>Name</th><th>Mobile</th><th>Property</th><th>Room / Bed</th><th>Rent</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($students as $s)
            <tr>
                <td>{{ $s->name }}</td>
                <td>{{ $s->mobile }}</td>
                <td>{{ $s->property?->name ?? '-' }}</td>
                <td>{{ $s->bed ? 'Room ' . $s->bed->room?->room_number . ' / Bed ' . $s->bed->label : 'Unassigned' }}</td>
                <td>{{ number_format($s->monthly_rent, 2) }}</td>
                <td>{{ ucfirst($s->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
