@extends('layouts.owner')

@section('title', 'Rooms — ' . $property->name)
@section('page-title', 'Rooms — ' . $property->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.properties.show', $property) }}" class="text-decoration-none small text-muted">
            <i class="bi bi-arrow-left me-1"></i> Back to {{ $property->name }}
        </a>
        <h4 class="fw-bold mb-0 mt-1">Rooms</h4>
    </div>
    <a href="{{ route('owner.properties.rooms.create', $property) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add Room
    </a>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Room No</th>
                    <th>Floor</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Occupied / Available</th>
                    <th>Monthly Rent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                <tr>
                    <td class="fw-bold">{{ $room->room_number }}</td>
                    <td>{{ $room->floor ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', ucfirst($room->type)) }}</span></td>
                    <td>{{ $room->isUnitType() ? 'Whole Unit' : $room->capacity }}</td>
                    <td>
                        @if($room->isUnitType())
                            <span class="badge bg-info-subtle text-info border">No Beds (Unit Rental)</span>
                        @else
                            <span class="text-danger fw-semibold">{{ $room->occupied_beds_count }} Occupied</span> /
                            <span class="text-success fw-semibold">{{ $room->available_beds_count }} Free</span>
                        @endif
                    </td>
                    <td>
                        ₹{{ number_format($room->rent_amount, 2) }}
                        <span class="badge bg-light text-muted border ms-1">{{ $room->rent_basis_label }}</span>
                    </td>
                    <td>
                        <a href="{{ route('owner.properties.rooms.show', [$property, $room]) }}" class="btn btn-sm btn-light border">View</a>
                        <a href="{{ route('owner.properties.rooms.edit', [$property, $room]) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('owner.properties.rooms.destroy', [$property, $room]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete room {{ $room->room_number }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No rooms found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rooms->links() }}</div>
@endsection
