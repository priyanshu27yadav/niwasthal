@extends('layouts.owner')

@section('title', 'Room ' . $room->room_number)
@section('page-title', 'Room ' . $room->room_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.properties.rooms.index', $property) }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to Rooms
        </a>
        <h4 class="fw-bold mb-0 mt-1">
            Room {{ $room->room_number }} ({{ str_replace('_', ' ', ucfirst($room->type)) }})
            <span class="badge bg-light text-dark border fs-6 ms-1">₹{{ number_format($room->rent_amount, 2) }} / {{ $room->rent_type === 'per_room' ? 'Room' : 'Bed' }}</span>
        </h4>
    </div>
    <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil me-1"></i> Edit Room
    </a>
</div>

@if($room->isUnitType() || $room->beds->isEmpty())
    <div class="lm-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="lm-stat-icon bg-info-subtle text-info"><i class="bi bi-shop-window"></i></div>
                <div>
                    <h6 class="fw-bold mb-1">{{ ucfirst($room->type) }} Unit — Charged {{ $room->rent_basis_label }}</h6>
                    <p class="text-muted small mb-0">
                        This unit does not use individual beds. Monthly rent is billed as a whole room/unit at <strong>₹{{ number_format($room->rent_amount, 2) }}</strong> ({{ $room->rent_basis_label }}).
                    </p>
                </div>
            </div>
            <a href="{{ route('owner.students.create', ['property_id' => $property->id, 'room_id' => $room->id]) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-person-plus me-1"></i> Assign {{ $occupantTerm ?? 'Student' }}
            </a>
        </div>
    </div>
    @if($room->activeStudents->isNotEmpty())
        <div class="row g-3">
            @foreach($room->activeStudents as $occupant)
                <div class="col-md-4">
                    <div class="lm-card p-3 border-primary border-top border-3">
                        <div class="fw-bold">{{ $occupant->name }}</div>
                        <div class="small text-muted mb-2">{{ $occupant->mobile }} &bull; ₹{{ number_format($occupant->monthly_rent, 2) }}/mo</div>
                        <a href="{{ route('owner.students.show', $occupant) }}" class="btn btn-sm btn-light border w-100">View Details</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@else
<div class="row g-3">
    @foreach($room->beds as $bed)
    <div class="col-md-4 col-lg-3">
        <div class="lm-card p-3 border-{{ $bed->is_occupied ? 'primary' : 'success' }} border-top border-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold fs-5">Bed {{ $bed->label }}</span>
                <span class="badge bg-{{ $bed->is_occupied ? 'primary' : 'success' }}-subtle text-{{ $bed->is_occupied ? 'primary' : 'success' }}">
                    {{ $bed->is_occupied ? 'Occupied' : 'Available' }}
                </span>
            </div>
            @if($bed->is_occupied && $bed->student)
                <div class="small fw-semibold">{{ $bed->student->name }}</div>
                <div class="small text-muted">{{ $bed->student->mobile }}</div>
                <a href="{{ route('owner.students.show', $bed->student) }}" class="btn btn-sm btn-light border w-100 mt-2">View Student</a>
            @else
                <div class="small text-muted mb-2">Ready for allocation</div>
                <a href="{{ route('owner.students.create', ['property_id' => $property->id, 'bed_id' => $bed->id]) }}" class="btn btn-sm btn-outline-success w-100">Assign Student</a>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
