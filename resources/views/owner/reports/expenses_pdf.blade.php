<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h2 { margin-bottom: 4px; color: #4f46e5; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 10px; text-align: left; }
        th { background-color: #f1f5f9; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Niwasthal — Expense Report ({{ $summary['year'] }})</h2>
    <p>Total Expenses: Rs. {{ number_format($summary['totalAmount'], 2) }}</p>
    <table>
        <thead><tr><th>Date</th><th>Property</th><th>Category</th><th>Description</th><th>Vendor</th><th class="text-right">Amount</th></tr></thead>
        <tbody>
            @foreach($expenses as $e)
            <tr>
                <td>{{ $e->expense_date?->format('Y-m-d') }}</td>
                <td>{{ $e->property?->name ?? '-' }}</td>
                <td>{{ ucfirst($e->category) }}</td>
                <td>{{ $e->description }}</td>
                <td>{{ $e->vendor ?? '-' }}</td>
                <td class="text-right">{{ number_format($e->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
