@extends('layouts.owner')

@section('title', 'Electricity Bill Detail — Niwasthal')
@section('page-title', 'Electricity Bill Detail')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.electricity.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Bills</a>
        <h4 class="fw-bold mb-0 mt-1">{{ $electricity->property?->name }} — {{ date('F', mktime(0,0,0,$electricity->month,1)) }} {{ $electricity->year }}</h4>
    </div>
    <form action="{{ route('owner.electricity.generate-allocations', $electricity) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-diagram-3 me-1"></i> Generate / Refresh Student Split
        </button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="lm-stat-card"><div class="small text-muted">Total Units</div><div class="fs-4 fw-bold">{{ $electricity->total_units }} kWh</div></div></div>
    <div class="col-md-3"><div class="lm-stat-card"><div class="small text-muted">Rate / Unit</div><div class="fs-4 fw-bold">₹{{ number_format($electricity->rate_per_unit, 2) }}</div></div></div>
    <div class="col-md-3"><div class="lm-stat-card"><div class="small text-muted">Fixed Charges</div><div class="fs-4 fw-bold">₹{{ number_format($electricity->fixed_charges, 2) }}</div></div></div>
    <div class="col-md-3"><div class="lm-stat-card"><div class="small text-muted">Total Bill</div><div class="fs-4 fw-bold text-primary">₹{{ number_format($electricity->total_amount, 2) }}</div></div></div>
</div>

<div class="lm-card overflow-hidden">
    <div class="p-3 border-bottom"><h6 class="fw-bold mb-0">Student Allocations</h6></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Student</th><th>Units</th><th>Amount</th><th>Paid</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($electricity->allocations as $alloc)
                <tr>
                    <td class="fw-semibold">{{ $alloc->student_name }}</td>
                    <td>{{ $alloc->units }} kWh</td>
                    <td class="fw-bold">₹{{ number_format($alloc->amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format($alloc->paid_amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $alloc->is_paid ? 'success' : 'warning' }}">
                            {{ $alloc->is_paid ? 'Paid' : 'Pending' }}
                        </span>
                    </td>
                    <td>
                        @if(!$alloc->is_paid)
                        <form action="{{ route('owner.electricity.allocations.mark-paid', $alloc) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Mark Paid</button>
                        </form>
                        @else
                        <span class="text-muted small"><i class="bi bi-check-all"></i> Settled</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Click "Generate / Refresh Student Split" to allocate this bill among active students.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
