@extends('layouts.student')

@section('title', 'Electricity Charges — Niwasthal')
@section('page-title', 'My Electricity Charges')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Total Billed</div><div class="fs-4 fw-bold">₹{{ number_format($totalAmount, 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Total Paid</div><div class="fs-4 fw-bold text-success">₹{{ number_format($totalPaid, 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Pending Due</div><div class="fs-4 fw-bold text-danger">₹{{ number_format($totalDue, 2) }}</div></div></div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Month/Year</th><th>Units</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($allocations as $alloc)
                <tr>
                    <td class="fw-semibold">{{ date('F', mktime(0,0,0,$alloc->bill->month,1)) }} {{ $alloc->bill->year }}</td>
                    <td>{{ $alloc->units }} kWh</td>
                    <td class="fw-bold">₹{{ number_format($alloc->amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format($alloc->paid_amount, 2) }}</td>
                    <td><span class="badge bg-{{ $alloc->is_paid ? 'success' : 'warning' }}">{{ $alloc->is_paid ? 'Paid' : 'Pending' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No electricity charges yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $allocations->links() }}</div>
@endsection
