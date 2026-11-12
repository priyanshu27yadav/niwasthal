@extends('layouts.owner')

@section('title', 'Announcements — Niwasthal')
@section('page-title', 'Announcements')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-3">Broadcast Announcement</h5>
            <form action="{{ route('owner.notifications.announcements.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Property *</label>
                    <select name="property_id" class="form-select" required>
                        @foreach($properties as $prop)
                            <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Message *</label>
                    <textarea name="body" rows="4" class="form-control" required></textarea>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="send_whatsapp" value="1" id="sendWa">
                    <label class="form-check-label small" for="sendWa">Also send via WhatsApp (if enabled)</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Publish Announcement</button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-3">Past Announcements</h5>
            @forelse($announcements as $ann)
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between">
                    <h6 class="fw-bold mb-1">{{ $ann->title }}</h6>
                    <span class="small text-muted">{{ $ann->published_at?->format('d M Y') }}</span>
                </div>
                <div class="small text-primary mb-1">{{ $ann->property?->name }}</div>
                <p class="small text-muted mb-0">{{ $ann->body }}</p>
            </div>
            @empty
            <p class="text-muted small mb-0">No announcements published yet.</p>
            @endforelse
            {{ $announcements->links() }}
        </div>
    </div>
</div>
@endsection
