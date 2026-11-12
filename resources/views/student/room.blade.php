@extends('layouts.student')

@section('title', 'My Room & Bed — Niwasthal')
@section('page-title', 'My Room & Bed')

@section('content')
<div class="row g-4">
    <div class="col-lg-6">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-3">Property Information</h5>
            <div class="mb-2"><span class="text-muted small">Property Name:</span><div class="fw-semibold">{{ $student->property?->name }}</div></div>
            <div class="mb-2"><span class="text-muted small">Address:</span><div>{{ $student->property?->address }}, {{ $student->property?->city }} {{ $student->property?->pincode }}</div></div>
            <div class="mb-2"><span class="text-muted small">Contact:</span><div>{{ $student->property?->contact_number ?? '-' }}</div></div>
            <div><span class="text-muted small">Facilities:</span><div>{{ $student->property?->facilities ?? 'Standard PG Amenities' }}</div></div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-3">Allocated Room & Bed</h5>
            @if($student->bed)
                <div class="mb-2"><span class="text-muted small">Room Number:</span><div class="fs-4 fw-bold text-primary">{{ $student->bed->room?->room_number }}</div></div>
                <div class="mb-2"><span class="text-muted small">Bed Label:</span><div class="fw-semibold">Bed {{ $student->bed->label }}</div></div>
                <div class="mb-2"><span class="text-muted small">Floor & Type:</span><div>{{ $student->bed->room?->floor ?? '-' }} • {{ str_replace('_', ' ', ucfirst($student->bed->room?->type)) }}</div></div>
                <div><span class="text-muted small">Monthly Rent:</span><div class="fw-bold text-success">₹{{ number_format($student->monthly_rent, 2) }}</div></div>
            @else
                <p class="text-muted mb-0">No bed currently assigned. Please contact your PG owner.</p>
            @endif
        </div>
    </div>
</div>
@endsection
