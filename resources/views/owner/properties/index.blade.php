@extends('layouts.owner')

@section('title', 'Properties — Niwasthal')
@section('page-title', 'Properties')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Your Properties</h4>
        <p class="text-muted small mb-0">Manage your PGs, hostels, and lodges.</p>
    </div>
    <a href="{{ route('owner.properties.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Property
    </a>
</div>

<div class="row g-4">
    @forelse($properties as $property)
    <div class="col-md-6 col-xl-4">
        <div class="lm-card p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $property->type }}</span>
                    <span class="badge bg-{{ $property->is_active ? 'success' : 'secondary' }}-subtle text-{{ $property->is_active ? 'success' : 'secondary' }}">
                        {{ $property->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <h5 class="fw-bold mb-1">{{ $property->name }}</h5>
                <p class="text-muted small mb-3">
                    <i class="bi bi-geo-alt me-1"></i>{{ $property->address }}, {{ $property->city }}
                </p>

                <div class="row g-2 text-center bg-light rounded-3 p-2 mb-3">
                    <div class="col-6 border-end">
                        <div class="fw-bold">{{ $property->rooms_count ?? $property->rooms()->count() }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Rooms</div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold">{{ $property->students_count ?? $property->students()->where('status', 'active')->count() }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Active Students</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('owner.properties.show', $property) }}" class="btn btn-primary btn-sm flex-grow-1">
                    Manage Rooms
                </a>
                <a href="{{ route('owner.properties.edit', $property) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="lm-card p-5 text-center">
            <i class="bi bi-building fs-1 text-muted"></i>
            <h5 class="fw-bold mt-3">No Properties Yet</h5>
            <p class="text-muted small">Add your first PG or lodge to start creating rooms and onboarding students.</p>
            <a href="{{ route('owner.properties.create') }}" class="btn btn-primary">Add Property</a>
        </div>
    </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $properties->links() }}
</div>
@endsection
