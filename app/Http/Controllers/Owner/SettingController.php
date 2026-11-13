<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\Setting;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * The list of settings managed by this controller, with their
     * expected types and default values.
     */
    private array $settingsConfig = [
        'pg_name'              => ['type' => 'string',  'default' => ''],
        'late_fee_amount'      => ['type' => 'numeric', 'default' => 0],
        'due_day'              => ['type' => 'integer', 'default' => 5],
        'rent_reminder_days'   => ['type' => 'integer', 'default' => 3],
        'whatsapp_enabled'     => ['type' => 'boolean', 'default' => false],
        'whatsapp_api_url'     => ['type' => 'string',  'default' => ''],
        'whatsapp_api_token'   => ['type' => 'string',  'default' => ''],
        'currency_symbol'      => ['type' => 'string',  'default' => '₹'],
        'timezone'             => ['type' => 'string',  'default' => 'Asia/Kolkata'],
        'email_notifications'  => ['type' => 'boolean', 'default' => true],
    ];

    /**
     * Show the settings form with current values.
     */
    public function index()
    {
        /** @var User $owner */
        $owner = Auth::user();

        // Load all current settings with their defaults
        $settings = [];
        foreach ($this->settingsConfig as $key => $config) {
            $settings[$key] = Setting::get($owner->id, $key, $config['default']);
        }

        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);

        return view('owner.settings.index', compact('settings', 'timezones', 'owner'));
    }

    /**
     * Update the owner's registration details (name, email, mobile, property type).
     */
    public function updateProfile(Request $request)
    {
        /** @var User $owner */
        $owner = Auth::user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($owner->id)],
            'mobile'        => ['required', 'string', 'max:15', Rule::unique('users')->ignore($owner->id)],
            'business_type' => 'required|in:pg,hostel,lodge',
        ]);

        $owner->update($validated);

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Profile and registration details updated successfully.');
    }

    /**
     * Generate and dispatch an OTP to the owner's mobile for updating bank/UPI payout details.
     */
    public function sendBankOtp(Request $request)
    {
        /** @var User $owner */
        $owner = Auth::user();
        $mobile = $owner->mobile;

        if (empty($mobile)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Please set your mobile number in Profile first.'], 422);
            }
            return back()->with('error', 'Please set your mobile number in Profile before requesting OTP.');
        }

        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        OtpVerification::where('mobile', $mobile)->delete();

        OtpVerification::create([
            'mobile'     => $mobile,
            'otp_hash'   => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            if (class_exists(WhatsAppService::class)) {
                app(WhatsAppService::class)->sendOtp($mobile, $otp);
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp OTP send failed: ' . $e->getMessage());
        }

        session()->flash('bank_otp_sent', true);
        session()->flash('bank_otp_demo', $otp);

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'message'  => "OTP sent to your registered mobile (+91 {$mobile}).",
                'otp_demo' => $otp,
            ]);
        }

        return back()->with('success', "Security OTP sent to +91 {$mobile}. Enter the 6-digit code below to verify and save your payout account. (Demo OTP: {$otp})");
    }

    /**
     * Verify OTP and update bank / UPI receiving details.
     */
    public function updateBankDetails(Request $request)
    {
        /** @var User $owner */
        $owner = Auth::user();
        $mobile = $owner->mobile;

        $request->validate([
            'otp'                 => 'required|string|size:6',
            'bank_name'           => 'nullable|string|max:100',
            'bank_account_holder' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_ifsc'           => 'nullable|string|max:20',
            'upi_id'              => 'nullable|string|max:100',
            'upi_qr_image'        => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
        ]);

        $otpRecord = OtpVerification::where('mobile', $mobile)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otpRecord || $otpRecord->isExpired() || !Hash::check($request->otp, $otpRecord->otp_hash)) {
            if ($otpRecord) {
                $otpRecord->increment('attempts');
            }
            return back()
                ->withInput()
                ->with('error', 'Invalid or expired OTP. Please click "Get OTP" to receive a fresh verification code.');
        }

        $otpRecord->update(['verified_at' => now()]);

        $data = [
            'bank_name'           => $request->bank_name,
            'bank_account_holder' => $request->bank_account_holder,
            'bank_account_number' => $request->bank_account_number,
            'bank_ifsc'           => $request->bank_ifsc ? strtoupper(trim($request->bank_ifsc)) : null,
            'upi_id'              => $request->upi_id ? strtolower(trim($request->upi_id)) : null,
            'bank_verified_at'    => now(),
        ];

        if ($request->hasFile('upi_qr_image')) {
            $file = $request->file('upi_qr_image');
            $uploadDir = public_path('uploads/qr');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'owner_' . $owner->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $data['upi_qr_image'] = 'uploads/qr/' . $filename;
        }

        $owner->update($data);

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Payout Bank Account & UPI details verified and saved successfully!');
    }

    /**
     * Save all settings using Setting::set().
     */
    public function update(Request $request)
    {
        $owner = Auth::user();

        $rules = [
            'pg_name'            => 'nullable|string|max:255',
            'late_fee_amount'    => 'nullable|numeric|min:0|max:99999',
            'due_day'            => 'nullable|integer|min:1|max:28',
            'rent_reminder_days' => 'nullable|integer|min:0|max:30',
            'whatsapp_enabled'   => 'nullable|boolean',
            'whatsapp_api_url'   => 'nullable|url|max:500',
            'whatsapp_api_token' => 'nullable|string|max:500',
            'currency_symbol'    => 'nullable|string|max:10',
            'timezone'           => 'nullable|string|timezone',
            'email_notifications'=> 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        foreach ($this->settingsConfig as $key => $config) {
            $value = match ($config['type']) {
                'boolean' => (bool) $request->boolean($key),
                'integer' => isset($validated[$key]) ? (int) $validated[$key] : (int) $config['default'],
                'numeric' => isset($validated[$key]) ? (float) $validated[$key] : (float) $config['default'],
                default   => $validated[$key] ?? $config['default'],
            };

            Setting::set($owner->id, $key, $value);
        }

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Settings saved successfully.');
    }

    /**
     * Update the owner's account password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('owner.settings.index')
            ->with('success', 'Password updated successfully.');
    }
}
