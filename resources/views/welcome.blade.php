@extends('layouts.auth')

@section('title', 'Niwasthal — Modern PG, Hostel & Lodge Management')

@section('content')
<div class="row justify-content-center align-items-center py-2">
    <div class="col-xl-10">
        <!-- Frosted Top Nav -->
        <div class="niwas-glass-nav px-3 px-md-4 py-3 mb-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <a href="{{ route('home') }}" class="text-decoration-none d-flex align-items-center gap-2">
                <span class="niwas-brand-badge" style="width: 42px; height: 42px; font-size: 1.15rem;">
                    <i class="bi bi-buildings-fill"></i>
                </span>
                <span class="d-flex flex-column lh-1">
                    <span class="fs-4 fw-bold text-dark">Niwasthal</span>
                    <small class="text-muted" style="font-size: 0.7rem;">Smart PG &amp; Lodge Platform</small>
                </span>
            </a>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('login', ['tab' => 'tenant']) }}" class="btn btn-sm btn-outline-success fw-semibold px-3 py-2">
                    <i class="bi bi-phone me-1"></i>Login as Tenant
                </a>
                <a href="{{ route('login') }}" class="btn btn-sm btn-primary fw-semibold px-3 py-2">
                    <i class="bi bi-building-gear me-1"></i>Login as Owner
                </a>
            </div>
        </div>

        <!-- Hero Section -->
        <div class="text-center mb-5 pt-1">
            <span class="badge bg-white text-primary border border-primary-subtle shadow-sm px-3 py-2 rounded-pill mb-3 fw-semibold">
                <i class="bi bi-stars text-warning me-1"></i> Complete PG, Hostel &amp; Lodge Operating System
            </span>
            <h1 class="display-5 fw-bold text-dark mb-3">
                Run Every Room, Rent &amp; Resident in <span class="niwas-gradient-text">One Place</span>
            </h1>
            <p class="lead text-muted mx-auto mb-4" style="max-width: 700px;">
                2-step property setup, live room inspector on dashboard, automated month-by-month billing, per-month online rent payment, electricity splits, security deposits, and instant PDF receipts.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <span class="niwas-feature-pill"><i class="bi bi-grid-3x3-gap-fill text-primary me-1"></i> Live Room Board</span>
                <span class="niwas-feature-pill"><i class="bi bi-shop text-info me-1"></i> PG Beds, Family Rooms &amp; Shops</span>
                <span class="niwas-feature-pill"><i class="bi bi-calendar2-check text-success me-1"></i> Month-by-Month Pay &amp; Receipts</span>
                <span class="niwas-feature-pill"><i class="bi bi-file-earmark-spreadsheet text-warning me-1"></i> PDF &amp; Excel Reports</span>
            </div>
        </div>

        <!-- 3-Step Visual Workflow Strip -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="niwas-step-card h-100 d-flex align-items-start gap-3">
                    <span class="lm-stat-icon bg-primary-subtle text-primary" style="width: 40px; height: 40px; font-size: 1rem;">1</span>
                    <div>
                        <div class="fw-bold small text-dark">Owner Creates Account</div>
                        <div class="text-muted" style="font-size: 0.78rem;">PG or Lodge owners register in seconds and configure floors &amp; rooms in 2 easy steps.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="niwas-step-card h-100 d-flex align-items-start gap-3">
                    <span class="lm-stat-icon bg-info-subtle text-info" style="width: 40px; height: 40px; font-size: 1rem;">2</span>
                    <div>
                        <div class="fw-bold small text-dark">Assign Room &amp; Monthly Rent</div>
                        <div class="text-muted" style="font-size: 0.78rem;">Add renters right from the Live Room Board and generate monthly rent bills with one click.</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="niwas-step-card h-100 d-flex align-items-start gap-3">
                    <span class="lm-stat-icon bg-success-subtle text-success" style="width: 40px; height: 40px; font-size: 1rem;">3</span>
                    <div>
                        <div class="fw-bold small text-dark">Tenants Login &amp; Pay Per Month</div>
                        <div class="text-muted" style="font-size: 0.78rem;">Tenants sign in via OTP, click Pay Now on any due month, and get instant paid date &amp; receipt.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portal Cards -->
        <div class="row g-4 justify-content-center">
            <div class="col-md-6">
                <div class="lm-card lm-card-elevated p-4 p-lg-5 h-100 d-flex flex-column justify-content-between border-0 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="lm-stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-building-gear"></i>
                            </div>
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold">PG &amp; Lodge Owners Only</span>
                        </div>
                        <h4 class="fw-bold mb-2">Owner &amp; Manager Portal</h4>
                        <p class="text-muted small mb-4">
                            Configure floors &amp; rooms in 2 steps, inspect occupied vs vacant rooms from the live dashboard, manage tenants/renters, and export financial reports.
                        </p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('login') }}" class="btn btn-primary fw-semibold py-2">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login as Owner
                        </a>
                        <a href="{{ route('owner.properties.create') }}" class="btn btn-outline-success fw-semibold py-2">
                            <i class="bi bi-building-add me-2"></i>Register Property (2 Easy Steps)
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-light border fw-medium py-2">
                            Create Owner Account (PG / Lodge Only)
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="lm-card lm-card-elevated p-4 p-lg-5 h-100 d-flex flex-column justify-content-between border-0 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 bg-success" style="height: 4px;"></div>
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="lm-stat-icon bg-success-subtle text-success">
                                <i class="bi bi-phone-fill"></i>
                            </div>
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">Tenant Login Only</span>
                        </div>
                        <h4 class="fw-bold mb-2">Tenant / Resident Portal</h4>
                        <p class="text-muted small mb-4">
                            Sign in securely using your registered mobile number &amp; OTP. View your room details, electricity split, pay rent month-by-month, download receipts, and raise complaints.
                        </p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('login', ['tab' => 'tenant']) }}" class="btn btn-success fw-semibold py-2">
                            <i class="bi bi-shield-check me-2"></i>Login as Tenant
                        </a>
                        <div class="p-3 rounded-3 bg-light border small text-muted mt-1">
                            <i class="bi bi-info-circle text-success me-1"></i> Tenant login only — accounts are created automatically when your PG or Lodge owner adds you.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
