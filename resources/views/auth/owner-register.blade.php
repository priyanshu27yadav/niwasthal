@extends('layouts.auth')

@section('title', 'Owner Registration — Niwasthal')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex flex-column align-items-center">
                <span class="niwas-brand-badge mb-2" style="width: 54px; height: 54px; font-size: 1.55rem;">
                    <i class="bi bi-buildings-fill"></i>
                </span>
                <h2 class="fw-bold text-dark mb-0">Niwasthal</h2>
            </a>
            <p class="text-muted small mt-1 mb-0">Owner Registration Only — PG, Hostel &amp; Lodge Owners</p>
        </div>

        <div class="lm-card lm-card-elevated p-4 p-md-5 border-0 position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>
            <div class="alert alert-primary bg-primary-subtle border-0 small py-2 mb-3">
                <i class="bi bi-building-check me-1"></i>
                <strong>Owners Only:</strong> Account creation is exclusively for PG, Hostel, or Lodge owners. Tenants can only log in via OTP after an owner adds them.
            </div>
            @if($errors->any())
                <div class="alert alert-danger small py-2">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Raj Kumar" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="raj@example.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mobile Number</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control" placeholder="9876543210" maxlength="15" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Property / Business Type</label>
                    <select name="business_type" class="form-select">
                        <option value="pg" {{ old('business_type', 'pg') === 'pg' ? 'selected' : '' }}>PG (Paying Guest)</option>
                        <option value="hostel" {{ old('business_type') === 'hostel' ? 'selected' : '' }}>Hostel</option>
                        <option value="lodge" {{ old('business_type') === 'lodge' ? 'selected' : '' }}>Lodge (Renters)</option>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                    Create Owner Account
                </button>
            </form>

            <hr class="my-4">
            <div class="text-center small">
                <span class="text-muted">Already registered?</span>
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Sign in</a>
            </div>
        </div>
    </div>
</div>
@endsection
