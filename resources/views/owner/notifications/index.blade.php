@extends('layouts.owner')

@section('title', 'Notifications — Niwasthal')
@section('page-title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Notifications</h4>
        <p class="text-muted small mb-0">Alerts for payments, complaints, and system events.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('owner.notifications.announcements') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-megaphone me-1"></i> Announcements
        </a>
        <form action="{{ route('owner.notifications.mark-all-read') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-light btn-sm border">Mark All Read</button>
        </form>
    </div>
</div>

<div class="lm-card overflow-hidden">
    <div class="list-group list-group-flush">
        @forelse($notifications as $notif)
        <div class="list-group-item p-3 {{ $notif->read_at ? '' : 'bg-primary-subtle bg-opacity-10' }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-semibold">{{ $notif->title }}</div>
                    <div class="small text-muted">{{ $notif->body }}</div>
                </div>
                <span class="small text-muted">{{ $notif->created_at?->diffForHumans() }}</span>
            </div>
        </div>
        @empty
        <div class="p-5 text-center text-muted">No notifications yet.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
