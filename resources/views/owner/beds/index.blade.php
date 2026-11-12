@extends('layouts.owner')

@section('title', 'Bed Matrix — ' . $property->name)
@section('page-title', 'Bed Matrix — ' . $property->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('owner.properties.show', $property) }}" class="text-decoration-none small text-muted">
        <i class="bi bi-arrow-left me-1"></i> Back to {{ $property->name }}
    </a>
    <h4 class="fw-bold mt-1">All Beds in {{ $property->name }}</h4>
</div>

<div class="row g-4">
    @foreach($rooms as $room)
    <div class="col-12">
        <div class="lm-card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Room {{ $room->room_number }} <span class="text-muted fw-normal small">({{ $room->floor ?? 'Ground' }} • ₹{{ number_format($room->rent_amount) }}/mo)</span></h6>
            </div>
            <div class="row g-3">
                @forelse($room->beds as $bed)
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <div class="p-3 rounded-3 border text-center {{ $bed->is_occupied ? 'bg-primary-subtle border-primary' : 'bg-success-subtle border-success' }}">
                        <div class="fw-bold">Bed {{ $bed->label }}</div>
                        <div class="small mt-1">{{ $bed->is_occupied ? ($bed->student->name ?? 'Occupied') : 'Available' }}</div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="p-3 rounded-3 border bg-info-subtle text-info small fw-semibold">
                        {{ ucfirst($room->type) }} Unit — No individual beds ({{ $room->rent_basis_label }}: ₹{{ number_format($room->rent_amount, 2) }}).
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
