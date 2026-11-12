@extends('layouts.owner')

@section('title', 'Rent Collection Report — Niwasthal')
@section('page-title', 'Rent Collection Report')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <a href="{{ route('owner.reports.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
        <h4 class="fw-bold mb-0 mt-1">Rent Collection — {{ $monthName }} {{ $year }}</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    </div>
</div>

<div class="lm-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Property</label>
            <select name="property_id" class="form-select form-select-sm">
                <option value="">All Properties</option>
                @foreach($properties as $p)
                    <option value="{{ $p->id }}" {{ request('property_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Month</label>
            <select name="month" class="form-select form-select-sm">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Year</label>
            <select name="year" class="form-select form-select-sm">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-dark btn-sm w-100">Apply</button></div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Total Billed</div><div class="fs-4 fw-bold">₹{{ number_format($summary['totalBilled'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Collected</div><div class="fs-4 fw-bold text-success">₹{{ number_format($summary['totalCollected'], 2) }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Pending</div><div class="fs-4 fw-bold text-danger">₹{{ number_format($summary['totalPending'], 2) }}</div></div></div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Student</th><th>Room</th><th>Rent</th><th>Paid</th><th>Outstanding</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->student_name }}</td>
                    <td>{{ $r->room_number ?? '-' }}</td>
                    <td>₹{{ number_format($r->total_amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format($r->paid_amount, 2) }}</td>
                    <td class="text-danger">₹{{ number_format($r->outstanding, 2) }}</td>
                    <td><span class="badge bg-{{ $r->status === 'paid' ? 'success' : ($r->status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($r->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No records for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
