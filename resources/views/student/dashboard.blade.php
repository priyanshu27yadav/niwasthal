@extends('layouts.student')

@section('title', 'Student Dashboard — Niwasthal')
@section('page-title', 'My Dashboard')

@section('content')
<div class="lm-hero-banner p-4 mb-4 border-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <span class="badge bg-white bg-opacity-25 text-white mb-2">Resident Portal</span>
            <h4 class="fw-bold mb-1">Welcome, {{ $student->name }}!</h4>
            <p class="mb-0 opacity-75 small">
                <i class="bi bi-building me-1"></i>{{ $student->property?->name }} &bull;
                @if($student->bed)
                    Room {{ $student->room_number }} (Bed {{ $student->bed->label }})
                @elseif($student->room)
                    Room {{ $student->room->room_number }} ({{ ucfirst($student->room->type) }})
                @else
                    Room Unassigned
                @endif
            </p>
        </div>
            @if($totalOutstanding > 0)
            <a href="{{ route('student.rent.index') }}" class="btn btn-light fw-semibold text-primary shadow-sm">
                <i class="bi bi-credit-card me-1"></i> Pay Due Rent (₹{{ number_format($totalOutstanding, 2) }})
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="small text-muted">Total Pending Dues (All Months)</div>
            <div class="fs-3 fw-bold {{ $totalOutstanding > 0 ? 'text-danger' : 'text-success' }} mt-1">
                ₹{{ number_format($totalOutstanding, 2) }}
            </div>
            @if($currentRent)
                <div class="small text-muted">
                    {{ now()->format('M Y') }} Status: <span class="badge bg-{{ $currentRent->status === 'paid' ? 'success' : ($currentRent->status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($currentRent->status) }}</span>
                </div>
            @else
                <div class="small text-muted">{{ $rentRecords->count() }} rent bill(s) recorded</div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="small text-muted">Security Deposit Held</div>
            <div class="fs-3 fw-bold text-primary mt-1">₹{{ number_format($student->security_deposit, 2) }}</div>
            <div class="small text-muted">Joined {{ $student->joining_date?->format('d M Y') }}</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="small text-muted">Open Complaints</div>
            <div class="fs-3 fw-bold mt-1">{{ $pendingComplaintsCount }}</div>
            <a href="{{ route('student.complaints.create') }}" class="small text-decoration-none">+ Submit New Request</a>
        </div>
    </div>
</div>

<!-- All Monthly Rent Bills with per-month Pay Now & Paid Timestamp -->
<div class="mb-4">
    @include('student.partials.rent-bills-table', [
        'records' => $rentRecords,
        'totalOutstanding' => $totalOutstanding,
        'razorpayConfigured' => $razorpayConfigured,
    ])
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="lm-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Recent Payments</h6>
                <a href="{{ route('student.payments.index') }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Receipt</th><th>Amount</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse($recentPayments as $p)
                        <tr>
                            <td class="font-monospace small">{{ $p->receipt_number }}</td>
                            <td class="text-success fw-bold">₹{{ number_format($p->amount, 2) }}</td>
                            <td class="small text-muted">{{ $p->paid_at?->format('d M Y') }}</td>
                            <td><a href="{{ route('student.payments.receipt', $p) }}" class="btn btn-sm btn-light border">Receipt</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="lm-card p-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-megaphone text-primary me-2"></i>Property Announcements</h6>
            @forelse($announcements as $ann)
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong class="small">{{ $ann->title }}</strong>
                        <span class="text-muted" style="font-size: 0.75rem;">{{ $ann->published_at?->format('d M') }}</span>
                    </div>
                    <p class="small text-muted mb-0">{{ $ann->body }}</p>
                </div>
            @empty
                <p class="text-muted small mb-0">No announcements at the moment.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
