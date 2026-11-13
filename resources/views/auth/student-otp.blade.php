@extends('layouts.auth')

@section('title', 'Student OTP Login — Niwasthal')

@section('content')
<div class="row justify-content-center" x-data="studentOtpLogin()">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="text-decoration-none">
                <span class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-3 mb-2" style="width: 48px; height: 48px; font-size: 1.4rem;">
                    <i class="bi bi-phone-fill"></i>
                </span>
                <h3 class="fw-bold text-dark mb-0">Student Portal</h3>
            </a>
            <p class="text-muted small mt-1">Sign in with your registered mobile number</p>
        </div>

        <div class="lm-card p-4 p-md-5 border-0 shadow">
            <div x-show="error" class="alert alert-danger small py-2" x-text="error" style="display: none;"></div>
            <div x-show="message" class="alert alert-success small py-2" x-text="message" style="display: none;"></div>
            <div x-show="debugOtp" class="alert alert-info small py-2" style="display: none;">
                <strong>Dev Mode OTP:</strong> <span class="font-monospace fw-bold" x-text="debugOtp"></span>
            </div>

            <!-- Step 1: Enter Mobile -->
            <div x-show="step === 1">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Registered Mobile Number</label>
                    <div class="input-group">
                        <span class="input-group-text">+91</span>
                        <input type="text" x-model="mobile" class="form-control" placeholder="9000000001" maxlength="10" @keyup.enter="sendOtp()">
                    </div>
                </div>
                <button type="button" @click="sendOtp()" :disabled="loading" class="btn btn-success w-100 fw-semibold py-2">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                    Send OTP
                </button>
            </div>

            <!-- Step 2: Verify OTP -->
            <div x-show="step === 2" style="display: none;">
                <p class="small text-muted mb-3">
                    Enter the 6-digit OTP sent to <strong x-text="mobile"></strong>
                    <a href="#" @click.prevent="step = 1; error = ''; message = ''" class="ms-1 small">Change</a>
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">6-Digit OTP</label>
                    <input type="text" x-model="otp" class="form-control text-center fs-5 letter-spacing-2" placeholder="000000" maxlength="6" @keyup.enter="verifyOtp()">
                </div>
                <button type="button" @click="verifyOtp()" :disabled="loading" class="btn btn-success w-100 fw-semibold py-2 mb-2">
                    <span x-show="loading" class="spinner-border spinner-border-sm me-1"></span>
                    Verify & Sign In
                </button>
                <button type="button" @click="sendOtp()" :disabled="loading" class="btn btn-link btn-sm w-100 text-muted">
                    Resend OTP
                </button>
            </div>

            <hr class="my-4">
            <div class="text-center small">
                <a href="{{ route('login') }}" class="text-muted text-decoration-none">
                    <i class="bi bi-building me-1"></i>PG Owner? Sign in here
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function studentOtpLogin() {
    return {
        step: 1,
        mobile: '',
        otp: '',
        loading: false,
        error: '',
        message: '',
        debugOtp: '',
        csrf: document.querySelector('meta[name="csrf-token"]').content,

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
                    if (data.otp_dev || data.debug_otp) {
                        this.debugOtp = data.otp_dev || data.debug_otp;
                        this.otp = this.debugOtp;
                    }
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
    }
}
</script>
@endpush
