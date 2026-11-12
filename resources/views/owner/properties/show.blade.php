@extends('layouts.owner')

@section('title', $property->name . ' — Niwasthal')
@section('page-title', $property->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">{{ $property->name }}</h4>
        <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-1"></i>{{ $property->address }}, {{ $property->city }} {{ $property->pincode }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('owner.properties.beds.index', $property) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-grid-3x3-gap me-1"></i> Bed Matrix
        </a>
        <a href="{{ route('owner.properties.rooms.create', $property) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Room
        </a>
        <a href="{{ route('owner.properties.edit', $property) }}" class="btn btn-light btn-sm border">
            <i class="bi bi-pencil me-1"></i> Edit
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="text-muted small">Total Rooms</div>
            <div class="fs-3 fw-bold">{{ $property->rooms_count }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="text-muted small">Active Students</div>
            <div class="fs-3 fw-bold text-primary">{{ $property->students_count }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="lm-stat-card">
            <div class="text-muted small">Facilities</div>
            <div class="small mt-1">{{ $property->facilities ?: 'None listed' }}</div>
        </div>
    </div>
</div>

<div class="lm-card overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Rooms in {{ $property->name }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Room No</th>
                    <th>Floor</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Occupied</th>
                    <th>Rent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($property->rooms as $room)
                <tr>
                    <td class="fw-bold">{{ $room->room_number }}</td>
                    <td>{{ $room->floor ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', ucfirst($room->type)) }}</span></td>
                    <td>{{ $room->isUnitType() ? 'Whole Unit' : ($room->beds_count ?? $room->capacity) . ' Beds' }}</td>
                    <td>
                        @if($room->isUnitType())
                            <span class="badge bg-info-subtle text-info border">No Beds</span>
                        @else
                            <span class="badge bg-{{ ($room->occupied_beds_count ?? 0) >= ($room->beds_count ?? $room->capacity) ? 'danger' : 'success' }}-subtle text-{{ ($room->occupied_beds_count ?? 0) >= ($room->beds_count ?? $room->capacity) ? 'danger' : 'success' }}">
                                {{ $room->occupied_beds_count ?? 0 }} / {{ $room->beds_count ?? $room->capacity }}
                            </span>
                        @endif
                    </td>
                    <td>
                        ₹{{ number_format($room->rent_amount, 2) }}
                        <span class="text-muted small">/ {{ $room->rent_type === 'per_room' ? 'room' : 'bed' }}</span>
                    </td>
                    <td>
                        <a href="{{ route('owner.properties.rooms.show', [$property, $room]) }}" class="btn btn-sm btn-light border">View</a>
                        <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No rooms added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
