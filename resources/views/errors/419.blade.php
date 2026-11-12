@extends('layouts.auth')

@section('title', 'Session Refreshed — Niwasthal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="lm-card p-4 p-md-5 text-center shadow border-0">
            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-info-subtle text-info" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="bi bi-arrow-clockwise"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Session Refreshed</h4>
            <p class="text-muted small mb-4">
                Your form token expired because the page sat open or another session changed. Click below to reload cleanly and continue.
            </p>
            <div class="d-grid gap-2">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn btn-primary fw-semibold py-2">
                    <i class="bi bi-arrow-left-circle me-2"></i>Go Back &amp; Retry
                </a>
                <a href="{{ route('home') }}" class="btn btn-light border fw-medium py-2">
                    <i class="bi bi-house me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
