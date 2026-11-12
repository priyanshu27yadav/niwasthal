@extends('layouts.owner')

@section('title', 'Reports — Niwasthal')
@section('page-title', 'Reports & Exports')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">Reports & Analytics</h4>
    <p class="text-muted small mb-0">Generate and export live PDF & Excel reports from your MySQL database.</p>
</div>

<div class="row g-4">
    @php
        $cards = [
            ['title' => 'Rent Collection Report', 'desc' => 'Monthly rent billed, collected, and pending breakdown.', 'icon' => 'bi-cash-stack', 'color' => 'primary', 'route' => 'owner.reports.rent-collection'],
            ['title' => 'Pending Dues Report', 'desc' => 'All unpaid and partially paid rent balances across properties.', 'icon' => 'bi-exclamation-triangle', 'color' => 'danger', 'route' => 'owner.reports.pending-dues'],
            ['title' => 'Occupancy Report', 'desc' => 'Room and bed occupancy rates across each property.', 'icon' => 'bi-door-open', 'color' => 'info', 'route' => 'owner.reports.occupancy'],
            ['title' => 'Expense Report', 'desc' => 'Category-wise and property-wise expense summaries.', 'icon' => 'bi-wallet2', 'color' => 'warning', 'route' => 'owner.reports.expenses'],
            ['title' => 'Student Directory Report', 'desc' => 'Complete list of active and vacated residents with room details.', 'icon' => 'bi-people', 'color' => 'success', 'route' => 'owner.reports.students'],
        ];
    @endphp

    @foreach($cards as $card)
    <div class="col-md-6 col-xl-4">
        <div class="lm-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="lm-stat-icon bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }} mb-3">
                    <i class="bi {{ $card['icon'] }}"></i>
                </div>
                <h5 class="fw-bold mb-2">{{ $card['title'] }}</h5>
                <p class="text-muted small mb-4">{{ $card['desc'] }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route($card['route']) }}" class="btn btn-primary btn-sm flex-grow-1">View Report</a>
                <a href="{{ route($card['route'], ['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm" title="Export PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <a href="{{ route($card['route'], ['format' => 'excel']) }}" class="btn btn-outline-success btn-sm" title="Export Excel"><i class="bi bi-file-earmark-excel"></i> Excel</a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
