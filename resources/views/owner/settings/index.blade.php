@extends('layouts.owner')

@section('title', 'Settings — Niwasthal')
@section('page-title', 'Settings & Account')

@section('content')
<div class="row g-4" x-data="bankSettings()">
    <!-- Column 1: Owner Registration Profile & Bank / UPI Payout Account -->
    <div class="col-lg-7">
        <!-- Card 1: Owner Profile Details (Filled during registration) -->
        <div class="lm-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary p-2 rounded-3"><i class="bi bi-person-badge fs-5"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0">Owner Registration Profile</h5>
                        <small class="text-muted">Manage your personal and business details used during registration</small>
                    </div>
                </div>
            </div>

            <form action="{{ route('owner.settings.profile') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $owner->name) }}" class="form-control" required>
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $owner->email) }}" class="form-control" required>
                        @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Registered Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">+91</span>
                            <input type="text" name="mobile" value="{{ old('mobile', $owner->mobile) }}" class="form-control" placeholder="10-digit number" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Used for security alerts and payout OTP verification.</small>
                        @error('mobile') <br><small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Property Type <span class="text-danger">*</span></label>
                        <select name="business_type" class="form-select" required>
                            <option value="pg" {{ old('business_type', $owner->business_type ?? 'pg') === 'pg' ? 'selected' : '' }}>Paying Guest (PG)</option>
                            <option value="hostel" {{ old('business_type', $owner->business_type) === 'hostel' ? 'selected' : '' }}>Hostel</option>
                            <option value="lodge" {{ old('business_type', $owner->business_type) === 'lodge' ? 'selected' : '' }}>Lodge</option>
                        </select>
                        @error('business_type') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="mt-4 pt-2 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Update Profile Details
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Bank Account & UPI Receiving Details (With OTP Protection) -->
        <div class="lm-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success p-2 rounded-3"><i class="bi bi-bank2 fs-5"></i></span>
                    <div>
                        <h5 class="fw-bold mb-0">Rent Receiving & Payout Account</h5>
                        <small class="text-muted">Bank account, UPI ID, and QR code where renters send rent payments</small>
                    </div>
                </div>

                @if($owner->bank_verified_at)
                    <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5">
                        <i class="bi bi-shield-fill-check"></i> OTP Verified & Active
                    </span>
                @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1 px-2.5 py-1.5">
                        <i class="bi bi-shield-exclamation"></i> OTP Verification Pending
                    </span>
                @endif
            </div>

            <div class="alert alert-info py-2 px-3 small border-0 bg-info-subtle text-info-emphasis rounded-3 mb-4 d-flex align-items-start gap-2">
                <i class="bi bi-shield-lock-fill fs-5 flex-shrink-0 text-info"></i>
                <div>
                    <strong>Financial Security Guard:</strong> When renters pay rent, the money is transferred to these details. For your security, any changes require a 6-digit OTP sent to your registered mobile <strong>+91 {{ $owner->mobile }}</strong>.
                </div>
            </div>

            <!-- OTP Notice if sent in current session -->
            @if(session('bank_otp_demo'))
                <div class="alert alert-warning py-2 px-3 small rounded-3 mb-3 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-key-fill me-1"></i> Security OTP generated for test/demo: <strong>{{ session('bank_otp_demo') }}</strong></span>
                    <button type="button" class="btn btn-xs btn-outline-dark" @click="otp = '{{ session('bank_otp_demo') }}'">Auto-fill OTP</button>
                </div>
            @endif

            <form action="{{ route('owner.settings.bank-details') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $owner->bank_name) }}" class="form-control" placeholder="e.g. State Bank of India, HDFC Bank">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account Holder Name</label>
                        <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $owner->bank_account_holder ?? $owner->name) }}" class="form-control" placeholder="Name as per passbook">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account Number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $owner->bank_account_number) }}" class="form-control" placeholder="e.g. 5010023456789">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">IFSC Code</label>
                        <input type="text" name="bank_ifsc" value="{{ old('bank_ifsc', $owner->bank_ifsc) }}" class="form-control text-uppercase" placeholder="e.g. SBIN0001234">
                    </div>

                    <div class="col-12"><hr class="my-2 border-secondary border-opacity-10"></div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">UPI ID (VPA)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" name="upi_id" value="{{ old('upi_id', $owner->upi_id) }}" class="form-control" placeholder="e.g. yourname@okaxis / 9876543210@paytm">
                        </div>
                        <small class="text-muted" style="font-size: 0.74rem;">Renters can copy or tap this UPI ID to pay directly from GPay, PhonePe, or Paytm.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Custom Payment QR Code Image</label>
                        <input type="file" name="upi_qr_image" class="form-control" accept="image/*" @change="previewImage($event)">
                        <small class="text-muted" style="font-size: 0.74rem;">Upload your PhonePe/GPay/Bank QR code scan poster (PNG, JPG, SVG).</small>

                        <!-- Existing QR Preview -->
                        <div class="mt-2 d-flex align-items-center gap-2">
                            @if($owner->upi_qr_image)
                                <div class="border rounded p-1 bg-white" style="width: 70px; height: 70px;">
                                    <img src="{{ asset($owner->upi_qr_image) }}" alt="Current QR" class="w-100 h-100 object-fit-contain rounded">
                                </div>
                                <span class="small text-muted">Current QR active</span>
                            @endif
                            <template x-if="imageUrl">
                                <div class="border rounded p-1 bg-white ms-2" style="width: 70px; height: 70px;">
                                    <img :src="imageUrl" alt="New Preview" class="w-100 h-100 object-fit-contain rounded">
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- OTP Verification Box -->
                    <div class="col-12 mt-3">
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-success me-1"></i> OTP Authorization Required</h6>
                                    <small class="text-muted">Click the button to receive an OTP on +91 {{ $owner->mobile }}</small>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" @click="requestOtp()" :disabled="otpSending">
                                    <span x-show="!otpSending"><i class="bi bi-send me-1"></i> Send OTP to Mobile</span>
                                    <span x-show="otpSending"><span class="spinner-border spinner-border-sm me-1"></span> Sending...</span>
                                </button>
                            </div>

                            <div class="row g-2 align-items-center mt-1">
                                <div class="col-sm-6">
                                    <input type="text" name="otp" x-model="otp" maxlength="6" class="form-control form-control-lg fw-bold text-center letter-spacing-2" placeholder="Enter 6-digit OTP" required autocomplete="one-time-code">
                                    @error('otp') <small class="text-danger">{{ $message }}</small> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <small class="text-muted" x-show="otpStatus" x-text="otpStatus"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-2 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-4 fw-semibold">
                        <i class="bi bi-shield-lock-fill me-1"></i> Verify OTP & Save Payout Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Column 2: System Settings & Change Password -->
    <div class="col-lg-5">
        <!-- Change Password Card -->
        <div class="lm-card p-4 mb-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-secondary-subtle text-secondary p-2 rounded-3"><i class="bi bi-key-fill fs-5"></i></span>
                <div>
                    <h5 class="fw-bold mb-0">Change Password</h5>
                    <small class="text-muted">Ensure your owner account is protected with a strong password</small>
                </div>
            </div>

            <form action="{{ route('owner.settings.password') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                    @error('current_password') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password (min 8 chars)</label>
                    <input type="password" name="password" class="form-control" required>
                    @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-lock-fill me-1"></i> Update Password
                </button>
            </form>
        </div>

        <!-- PG & System Preferences Card -->
        <div class="lm-card p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-info-subtle text-info p-2 rounded-3"><i class="bi bi-sliders fs-5"></i></span>
                <div>
                    <h5 class="fw-bold mb-0">Billing & System Rules</h5>
                    <small class="text-muted">Rent due dates, late fees, and notifications</small>
                </div>
            </div>

            <form action="{{ route('owner.settings.update') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Property / Business Display Name</label>
                        <input type="text" name="pg_name" value="{{ $settings['pg_name'] ?? '' }}" class="form-control" placeholder="e.g. Kishore Lodge">
                    </div>

                    <div class="col-6">
                        <label class="form-label small fw-semibold">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '₹' }}" class="form-control">
                    </div>

                    <div class="col-6">
                        <label class="form-label small fw-semibold">Rent Due Day (Monthly)</label>
                        <input type="number" name="due_day" value="{{ $settings['due_day'] ?? 5 }}" min="1" max="28" class="form-control">
                    </div>

                    <div class="col-6">
                        <label class="form-label small fw-semibold">Default Late Fee (₹)</label>
                        <input type="number" name="late_fee_amount" value="{{ $settings['late_fee_amount'] ?? 0 }}" step="0.01" class="form-control">
                    </div>

                    <div class="col-6">
                        <label class="form-label small fw-semibold">Reminder Days</label>
                        <input type="number" name="rent_reminder_days" value="{{ $settings['rent_reminder_days'] ?? 3 }}" min="0" max="30" class="form-control">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-top pt-3 small text-uppercase text-muted">WhatsApp Notifications</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="whatsapp_enabled" value="1" id="waEnabled" {{ !empty($settings['whatsapp_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="waEnabled">Enable WhatsApp Notifications</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">WhatsApp Cloud API URL</label>
                        <input type="url" name="whatsapp_api_url" value="{{ $settings['whatsapp_api_url'] ?? '' }}" class="form-control form-control-sm" placeholder="https://graph.facebook.com/v18.0/...">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">WhatsApp Bearer Token</label>
                        <input type="password" name="whatsapp_api_token" value="{{ $settings['whatsapp_api_token'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-save me-1"></i> Save Billing Rules
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bankSettings() {
    return {
        otp: '{{ old('otp', session('bank_otp_demo', '')) }}',
        otpSending: false,
        otpStatus: '{{ session('bank_otp_sent') ? "OTP sent to registered mobile." : "" }}',
        imageUrl: null,
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.imageUrl = URL.createObjectURL(file);
            }
        },
        async requestOtp() {
            this.otpSending = true;
            this.otpStatus = 'Sending OTP...';
            try {
                const response = await fetch('{{ route('owner.settings.bank-otp.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.otpStatus = data.message;
                    if (data.otp_demo) {
                        this.otp = data.otp_demo;
                        this.otpStatus += ' (Auto-filled Demo OTP: ' + data.otp_demo + ')';
                    }
                } else {
                    this.otpStatus = data.message || 'Failed to send OTP.';
                }
            } catch (err) {
                this.otpStatus = 'Error contacting server. Please try again.';
            } finally {
                this.otpSending = false;
            }
        }
    };
}
</script>
@endsection
