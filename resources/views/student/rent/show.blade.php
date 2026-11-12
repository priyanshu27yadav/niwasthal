@extends('layouts.student')

@section('title', 'Rent Breakdown — Niwasthal')
@section('page-title', 'Rent Breakdown')

@section('content')
<div class="mb-3">
    <a href="{{ route('student.rent.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Rent History</a>
</div>

<div class="lm-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">{{ date('F', mktime(0,0,0,$rent->month,1)) }} {{ $rent->year }} Rent</h5>
        <span class="badge bg-{{ $rent->status === 'paid' ? 'success' : ($rent->status === 'partial' ? 'warning' : 'danger') }} fs-6">{{ ucfirst($rent->status) }}</span>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="small text-muted">Base Rent</div><div class="fw-bold">₹{{ number_format($rent->rent_amount, 2) }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Late Fee</div><div class="fw-bold">₹{{ number_format($rent->late_fee, 2) }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Paid</div><div class="fw-bold text-success">₹{{ number_format($rent->paid_amount, 2) }}</div></div>
        <div class="col-md-3"><div class="small text-muted">Outstanding</div><div class="fw-bold text-danger">₹{{ number_format($rent->outstanding, 2) }}</div></div>
    </div>

    <h6 class="fw-bold mb-2">Payments Applied</h6>
    <table class="table table-sm">
        <thead><tr><th>Receipt</th><th>Date</th><th>Allocated Amount</th></tr></thead>
        <tbody>
            @forelse($rent->allocations as $alloc)
            <tr>
                <td class="font-monospace small">{{ $alloc->payment?->receipt_number }}</td>
                <td class="small">{{ $alloc->payment?->paid_at?->format('d M Y') }}</td>
                <td class="fw-semibold text-success">₹{{ number_format($alloc->amount, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-muted small">No payments applied yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
