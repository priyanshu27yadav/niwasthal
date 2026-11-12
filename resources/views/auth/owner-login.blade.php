@extends('layouts.auth')

@section('title', 'Login — Niwasthal')

@section('content')
@php
    $resolvedTab = $errors->has('email') || old('email') ? 'owner' : ($initialTab ?? (request('tab') === 'tenant' ? 'tenant' : 'owner'));
@endphp
<div class="row justify-content-center align-items-center" x-data="unifiedLogin(@js($resolvedTab))">
    <div class="col-md-8 col-lg-5">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex flex-column align-items-center">
                <span class="niwas-brand-badge mb-2" style="width: 54px; height: 54px; font-size: 1.55rem;">
                    <i class="bi bi-buildings-fill"></i>
                </span>
                <h2 class="fw-bold text-dark mb-0">Niwasthal</h2>
            </a>
            <p class="text-muted small mt-1 mb-0">Unified Owner &amp; Tenant Sign-In Portal</p>
        </div>

        <div class="lm-card lm-card-elevated p-4 p-md-5 border-0 position-relative overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100"
                 :class="tab === 'owner' ? 'bg-primary' : 'bg-success'"
                 style="height: 4px; transition: background-color 0.2s ease;"></div>

            <!-- Role Switcher Tabs on Single Login Page -->
            <div class="d-grid gap-2 d-sm-flex mb-4 p-1 bg-light rounded-3 border" role="group" aria-label="Login role selector">
                <button type="button"
                        @click="switchTab('owner')"
                        :class="tab === 'owner' ? 'btn btn-primary text-white shadow-sm' : 'btn btn-light text-muted border-0'"
                        class="flex-fill fw-semibold py-2 rounded-2">
                    <i class="bi bi-building-gear me-1"></i> Login as Owner
                </button>
                <button type="button"
                        @click="switchTab('tenant')"
                        :class="tab === 'tenant' ? 'btn btn-success text-white shadow-sm' : 'btn btn-light text-muted border-0'"
                        class="flex-fill fw-semibold py-2 rounded-2">
                    <i class="bi bi-phone-fill me-1"></i> Login as Tenant
                </button>
            </div>

            @if(session('error'))
                <div class="alert alert-warning border-0 shadow-sm small py-2 mb-3">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                </div>
            @endif

            <!-- TAB 1: OWNER LOGIN -->
            <div x-show="tab === 'owner'">
                @if($errors->any())
                    <div class="alert alert-danger border-0 small py-2">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Owner Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope text-primary"></i></span>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="owner@niwasthal.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock text-primary"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label small text-muted" for="remember">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login as Owner
                    </button>
                </form>

                <!-- Owner Only: Create Account & Register Property -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-dark">
                            <i class="bi bi-shield-check text-primary me-1"></i>For PG Owners &amp; Lodge Owners Only
                        </span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('register') }}" class="btn btn-outline-primary fw-semibold btn-sm py-2">
                            <i class="bi bi-person-plus me-1"></i>Create Owner Account (PG / Lodge Only)
                        </a>
                        <a href="{{ route('owner.properties.create') }}" class="btn btn-outline-success fw-semibold btn-sm py-2">
                            <i class="bi bi-building-add me-1"></i>Register Property (2 Easy Steps)
                        </a>
                    </div>
                </div>
            </div>

            <!-- TAB 2: TENANT OTP LOGIN (LOGIN ONLY — NO SIGN UP) -->
            <div x-show="tab === 'tenant'" x-cloak>
                <div class="alert alert-success bg-success-subtle border-0 small text-dark py-2 px-3 mb-3 rounded-3">
                    <i class="bi bi-info-circle-fill text-success me-1"></i>
                    <strong>Tenant Login Only:</strong> Tenant accounts are added by your PG or Lodge owner. Enter your registered mobile number to sign in via OTP.
                </div>

                <div x-show="error" class="alert alert-danger border-0 small py-2" x-text="error" style="display: none;"></div>
                <div x-show="message" class="alert alert-success border-0 small py-2" x-text="message" style="display: none;"></div>
                <div x-show="debugOtp" class="alert alert-info border-0 small py-2" style="display: none;">
                    <strong>Dev Mode OTP:</strong> <span class="font-monospace fw-bold" x-text="debugOtp"></span>
                </div>

                <!-- Step 1: Enter Mobile -->
                <div x-show="step === 1">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Registered Tenant Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text fw-semibold">+91</span>
                            <input type="text" x-model="mobile" class="form-control" placeholder="9000000001" maxlength="15" @keyup.enter="sendOtp()">
                        </div>
                    </div>
                    <button type="button" @click="sendOtp()" :disabled="loading" class="btn btn-success w-100 fw-semibold py-2">
                        <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                        <i x-show="!loading" class="bi bi-send-check me-1"></i>
                        Send OTP to Login as Tenant
                    </button>
                </div>

                <!-- Step 2: Verify OTP -->
                <div x-show="step === 2" style="display: none;">
                    <p class="small text-muted mb-3">
                        Enter the 6-digit OTP sent to <strong class="text-dark" x-text="mobile"></strong>
                        <a href="#" @click.prevent="step = 1; error = ''; message = ''" class="ms-1 small fw-semibold">Change</a>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">6-Digit OTP</label>
                        <input type="text" x-model="otp" class="form-control text-center fs-5 fw-bold letter-spacing-2" placeholder="000000" maxlength="6" @keyup.enter="verifyOtp()">
                    </div>
                    <button type="button" @click="verifyOtp()" :disabled="loading" class="btn btn-success w-100 fw-semibold py-2 mb-2">
                        <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                        Verify &amp; Login as Tenant
                    </button>
                    <button type="button" @click="sendOtp()" :disabled="loading" class="btn btn-link btn-sm w-100 text-muted">
                        Resend OTP
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function unifiedLogin(defaultTab) {
    return {
        tab: defaultTab || 'owner',
        step: 1,
        mobile: '',
        otp: '',
        loading: false,
        error: '',
        message: '',
        debugOtp: '',
        csrf: document.querySelector('meta[name="csrf-token"]').content,

        switchTab(next) {
            this.tab = next;
            this.error = '';
            this.message = '';
        },

        async sendOtp() {
            this.error = '';
            this.message = '';
            this.loading = true;
            try {
                const res = await fetch('{{ route("student.otp.send") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ mobile: this.mobile })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Failed to send OTP.');
                } else {
                    this.step = 2;
                    this.message = data.message || 'OTP sent!';
                    if (data.otp_dev || data.debug_otp) this.debugOtp = data.otp_dev || data.debug_otp;
                }
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        async verifyOtp() {
            this.error = '';
            this.loading = true;
            try {
                const res = await fetch('{{ route("student.otp.verify") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ mobile: this.mobile, otp: this.otp })
                });
                const data = await res.json();
                if (!res.ok) {
                    this.error = data.message || 'Invalid OTP.';
                } else {
                    window.location.href = data.redirect || '{{ route("student.dashboard") }}';
                }
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
