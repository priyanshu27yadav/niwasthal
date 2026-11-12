@extends('layouts.owner')

@section('title', ($occupantTermPlural ?? 'Students') . ' — Niwasthal')
@section('page-title', $occupantTermPlural ?? 'Students')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">{{ $occupantTermPlural ?? 'Students' }} & Residents</h4>
        <p class="text-muted small mb-0">Manage {{ strtolower($occupantTerm ?? 'student') }} profiles, room/bed allocations, and monthly rents.</p>
    </div>
    <a href="{{ route('owner.students.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus-fill me-1"></i> Add {{ $occupantTerm ?? 'Student' }}
    </a>
</div>

<div class="lm-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Name, mobile, or email...">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Property</label>
            <select name="property_id" class="form-select form-select-sm">
                <option value="">All Properties</option>
                @foreach($properties as $prop)
                    <option value="{{ $prop->id }}" {{ request('property_id') == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="vacated" {{ request('status') === 'vacated' ? 'selected' : '' }}>Vacated</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="{{ route('owner.students.index') }}" class="btn btn-light btn-sm border">Reset</a>
        </div>
    </form>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ $occupantTerm ?? 'Student' }}</th>
                    <th>Property</th>
                    <th>Room / Bed</th>
                    <th>Monthly Rent</th>
                    <th>Joining Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $student->name }}</div>
                        <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $student->mobile }}</div>
                    </td>
                    <td>{{ $student->property?->name ?? '-' }}</td>
                    <td>
                        @if($student->bed)
                            <span class="badge bg-light text-dark border">Room {{ $student->room_number }} • Bed {{ $student->bed->label }}</span>
                        @elseif($student->room)
                            <span class="badge bg-info-subtle text-info border">Room {{ $student->room->room_number }} ({{ ucfirst($student->room->type) }})</span>
                        @else
                            <span class="text-muted small">Unassigned</span>
                        @endif
                    </td>
                    <td class="fw-semibold">₹{{ number_format($student->monthly_rent, 2) }}</td>
                    <td class="small text-muted">{{ $student->joining_date?->format('d M Y') }}</td>
                    <td>
                        <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $student->status === 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($student->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('owner.students.show', $student) }}" class="btn btn-sm btn-light border">View</a>
                        <a href="{{ route('owner.students.edit', $student) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $students->links() }}</div>
@endsection
