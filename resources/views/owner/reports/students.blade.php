@extends('layouts.owner')

@section('title', 'Students Report — Niwasthal')
@section('page-title', 'Students Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.reports.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
        <h4 class="fw-bold mb-0 mt-1">Student Directory Report</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    </div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Name</th><th>Mobile</th><th>Property</th><th>Room / Bed</th><th>Monthly Rent</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td class="fw-semibold">{{ $s->name }}</td>
                    <td>{{ $s->mobile }}</td>
                    <td>{{ $s->property?->name ?? '-' }}</td>
                    <td>{{ $s->bed ? 'Room ' . $s->bed->room?->room_number . ' / Bed ' . $s->bed->label : 'Unassigned' }}</td>
                    <td>₹{{ number_format($s->monthly_rent, 2) }}</td>
                    <td><span class="badge bg-{{ $s->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($s->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
