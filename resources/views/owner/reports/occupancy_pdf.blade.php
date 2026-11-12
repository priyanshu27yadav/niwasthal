<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Occupancy Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h2 { margin-bottom: 4px; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { background-color: #f1f5f9; }
    </style>
</head>
<body>
    <h2>Niwasthal — Occupancy Report</h2>
    <p>Total Beds: {{ $summary['totalBeds'] }} | Occupied: {{ $summary['occupiedBeds'] }} | Overall Occupancy: {{ $summary['overallRate'] }}%</p>
    <table>
        <thead><tr><th>Property</th><th>Rooms</th><th>Total Beds</th><th>Occupied</th><th>Vacant</th><th>Rate</th></tr></thead>
        <tbody>
            @foreach($occupancyData as $row)
            <tr>
                <td>{{ $row['property']->name }}</td>
                <td>{{ $row['total_rooms'] }}</td>
                <td>{{ $row['total_beds'] }}</td>
                <td>{{ $row['occupied_beds'] }}</td>
                <td>{{ $row['vacant_beds'] }}</td>
                <td>{{ $row['occupancy_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
