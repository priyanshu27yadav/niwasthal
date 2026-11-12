@extends('layouts.owner')

@section('title', $complaint->title)
@section('page-title', 'Complaint Detail')

@section('content')
<div class="mb-3">
    <a href="{{ route('owner.complaints.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Complaints</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="lm-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-1">{{ $complaint->title }}</h4>
                    <div class="small text-muted">
                        By <strong>{{ $complaint->student?->name }}</strong> ({{ $complaint->property?->name }}) • {{ $complaint->created_at?->format('d M Y, h:i A') }}
                    </div>
                </div>
                <span class="badge bg-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'in_progress' ? 'info' : 'warning') }} fs-6">
                    {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                </span>
            </div>

            <p class="mb-4">{{ $complaint->description }}</p>

            @if($complaint->attachments->count())
                <h6 class="fw-bold small text-muted text-uppercase mb-2">Attachments</h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($complaint->attachments as $att)
                        <a href="{{ asset('storage/' . $att->path) }}" target="_blank">
                            <img src="{{ asset('storage/' . $att->path) }}" class="rounded border" style="width: 110px; height: 110px; object-fit: cover;">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-5">
        <div class="lm-card p-4">
            <h6 class="fw-bold mb-3">Update Status & Respond</h6>
            <form action="{{ route('owner.complaints.update-status', $complaint) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" {{ $complaint->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $complaint->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="rejected" {{ $complaint->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Owner Response</label>
                    <textarea name="owner_response" rows="4" class="form-control" placeholder="Add update or resolution notes for the student...">{{ old('owner_response', $complaint->owner_response) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Complaint</button>
            </form>
        </div>
    </div>
</div>
@endsection
