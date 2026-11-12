@extends('layouts.owner')

@section('title', 'Settings — Niwasthal')
@section('page-title', 'Settings')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">PG & Billing Settings</h5>
            <form action="{{ route('owner.settings.update') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">PG / Organization Name</label>
                        <input type="text" name="pg_name" value="{{ $settings['pg_name'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '₹' }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Rent Due Day</label>
                        <input type="number" name="due_day" value="{{ $settings['due_day'] ?? 5 }}" min="1" max="28" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Default Late Fee (₹)</label>
                        <input type="number" name="late_fee_amount" value="{{ $settings['late_fee_amount'] ?? 0 }}" step="0.01" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Reminder Days Before Due</label>
                        <input type="number" name="rent_reminder_days" value="{{ $settings['rent_reminder_days'] ?? 3 }}" min="0" max="30" class="form-control">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="fw-bold border-top pt-3">WhatsApp Business Cloud API</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="whatsapp_enabled" value="1" id="waEnabled" {{ !empty($settings['whatsapp_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="waEnabled">Enable WhatsApp Notifications</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">WhatsApp API URL</label>
                        <input type="url" name="whatsapp_api_url" value="{{ $settings['whatsapp_api_url'] ?? '' }}" class="form-control" placeholder="https://graph.facebook.com/v18.0/...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">WhatsApp API Token</label>
                        <input type="password" name="whatsapp_api_token" value="{{ $settings['whatsapp_api_token'] ?? '' }}" class="form-control">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Change Password</h5>
            <form action="{{ route('owner.settings.password') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-dark w-100">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
