@extends('layouts.student')

@section('title', 'Notifications — Niwasthal')
@section('page-title', 'Notifications')

@section('content')
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
        <div class="p-5 text-center text-muted">You have no notifications.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
