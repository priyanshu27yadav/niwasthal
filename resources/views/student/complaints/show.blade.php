@extends('layouts.student')

@section('title', $complaint->title)
@section('page-title', 'Complaint Status')

@section('content')
<div class="mb-3">
    <a href="{{ route('student.complaints.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Complaints</a>
</div>

<div class="lm-card p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="fw-bold mb-1">{{ $complaint->title }}</h4>
            <div class="small text-muted">{{ ucfirst($complaint->category) }} • {{ ucfirst($complaint->priority) }} Priority • Submitted {{ $complaint->created_at?->format('d M Y, h:i A') }}</div>
        </div>
        <span class="badge bg-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'in_progress' ? 'info' : 'warning') }} fs-6">
            {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
        </span>
    </div>

    <p class="mb-4">{{ $complaint->description }}</p>

    @if($complaint->attachments->count())
        <div class="d-flex flex-wrap gap-2 mb-4">
            @foreach($complaint->attachments as $att)
                <img src="{{ asset('storage/' . $att->path) }}" class="rounded border" style="width: 110px; height: 110px; object-fit: cover;">
            @endforeach
        </div>
    @endif

    @if($complaint->owner_response)
        <div class="alert alert-info border-0 mb-0">
            <h6 class="fw-bold mb-1"><i class="bi bi-reply-fill me-1"></i> Owner Response</h6>
            <p class="mb-0 small">{{ $complaint->owner_response }}</p>
        </div>
    @endif
</div>
@endsection
