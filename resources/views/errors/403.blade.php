@extends('layouts.auth')

@section('title', 'Access Denied — Niwasthal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="lm-card p-4 p-md-5 text-center shadow border-0">
            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning" style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">Portal Access Switch Needed</h4>
            <p class="text-muted small mb-4">
                {{ $exception->getMessage() ?: 'This page belongs to a different portal role.' }}
                Please sign in to the matching portal below — Owner and Renter/Student sessions can now stay open at the same time.
            </p>
            <div class="d-grid gap-2">
                <a href="{{ route('login') }}" class="btn btn-primary fw-semibold py-2">
                    <i class="bi bi-building-gear me-2"></i>Go to Owner Login
                </a>
                <a href="{{ route('student.otp') }}" class="btn btn-outline-success fw-semibold py-2">
                    <i class="bi bi-phone-fill me-2"></i>Go to Student / Renter OTP Login
                </a>
                <a href="{{ route('home') }}" class="btn btn-light border fw-medium py-2">
                    <i class="bi bi-house me-1"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
