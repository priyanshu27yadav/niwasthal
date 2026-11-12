@extends('layouts.owner')

@section('title', 'Occupancy Report — Niwasthal')
@section('page-title', 'Occupancy Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.reports.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
        <h4 class="fw-bold mb-0 mt-1">Occupancy Report</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Total Beds</div><div class="fs-4 fw-bold">{{ $summary['totalBeds'] }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Occupied Beds</div><div class="fs-4 fw-bold text-primary">{{ $summary['occupiedBeds'] }}</div></div></div>
    <div class="col-md-4"><div class="lm-stat-card"><div class="small text-muted">Overall Occupancy</div><div class="fs-4 fw-bold text-success">{{ $summary['overallRate'] }}%</div></div></div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Property</th><th>Total Rooms</th><th>Total Beds</th><th>Occupied</th><th>Vacant</th><th>Rate</th></tr></thead>
            <tbody>
                @foreach($occupancyData as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['property']->name }}</td>
                    <td>{{ $row['total_rooms'] }}</td>
                    <td>{{ $row['total_beds'] }}</td>
                    <td class="text-primary fw-semibold">{{ $row['occupied_beds'] }}</td>
                    <td class="text-success fw-semibold">{{ $row['vacant_beds'] }}</td>
                    <td><span class="badge bg-info-subtle text-info">{{ $row['occupancy_rate'] }}%</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
