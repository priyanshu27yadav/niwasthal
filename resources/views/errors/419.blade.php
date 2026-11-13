@extends('layouts.auth')

@section('title', 'Session Refreshed — Niwasthal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="lm-card p-4 p-md-5 text-center shadow border-0">
            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-info-subtle text-info" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="bi bi-arrow-clockwise"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Session Timed Out</h4>
            <p class="text-muted small mb-4">
                Your sign-in session expired because the page was open for a while or cookies refreshed. Click below to load a fresh login form.
            </p>
            <div class="d-grid gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary fw-semibold py-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Go to Login Page
                </a>
                <a href="{{ route('register') }}" class="btn btn-outline-primary fw-semibold py-2">
                    <i class="bi bi-person-plus me-1"></i> Create Owner Account (PG / Lodge Only)
                </a>
                <a href="{{ route('home') }}" class="btn btn-light border fw-medium py-2">
                    <i class="bi bi-house me-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
