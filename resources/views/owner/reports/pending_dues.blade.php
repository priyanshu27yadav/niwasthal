@extends('layouts.owner')

@section('title', 'Pending Dues Report — Niwasthal')
@section('page-title', 'Pending Dues Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.reports.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
        <h4 class="fw-bold mb-0 mt-1">Pending Dues Report</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Total Outstanding</div><div class="fs-4 fw-bold text-danger">₹{{ number_format($summary['totalDues'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Completely Unpaid</div><div class="fs-4 fw-bold">₹{{ number_format($summary['unpaidAmount'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Partially Paid Balance</div><div class="fs-4 fw-bold text-warning">₹{{ number_format($summary['partialAmount'], 2) }}</div></div></div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Student</th><th>Mobile</th><th>Room</th><th>Month/Year</th><th>Due Date</th><th>Outstanding</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($pendingRecords as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->student_name }}</td>
                    <td>{{ $r->student?->mobile ?? '-' }}</td>
                    <td>{{ $r->room_number ?? '-' }}</td>
                    <td>{{ date('M', mktime(0,0,0,$r->month,1)) }} {{ $r->year }}</td>
                    <td class="small">{{ $r->due_date?->format('d M Y') ?? '-' }}</td>
                    <td class="text-danger fw-bold">₹{{ number_format($r->outstanding, 2) }}</td>
                    <td><span class="badge bg-{{ $r->status === 'partial' ? 'warning' : 'danger' }}">{{ ucfirst($r->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Zero pending dues!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
