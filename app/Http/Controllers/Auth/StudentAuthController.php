<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class StudentAuthController extends Controller
{
    public function showOtpPage()
    {
        $webUser = Auth::guard('web')->user();
        if (Auth::guard('student')->check() || ($webUser && $webUser->isStudent())) {
            return redirect()->route('student.dashboard');
        }
        return view('auth.owner-login', ['initialTab' => 'tenant']);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['mobile' => 'required|string|max:15']);

        $mobile = $request->mobile;

        // Rate limit: max 3 OTP sends per 10 minutes per mobile
        $key = 'otp-send:' . $mobile;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many OTP requests. Try again in {$seconds} seconds.",
            ], 429);
        }

        // Check student exists
        $student = Student::where('mobile', $mobile)->where('status', 'active')->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number not registered. Please contact your PG owner.',
            ], 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old OTPs for this mobile
        OtpVerification::where('mobile', $mobile)->delete();

        // Store hashed OTP
        OtpVerification::create([
            'mobile' => $mobile,
            'otp_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        RateLimiter::hit($key, 600);

        // In production, send via WhatsApp/SMS. For dev, return in response.
        $message = "Your Niwasthal OTP is: {$otp}. Valid for 10 minutes.";

        // Try WhatsApp service if configured
        try {
            app(\App\Services\WhatsAppService::class)->sendOtp($mobile, $otp);
        } catch (\Exception $e) {
            // Log but don't fail — dev mode shows OTP
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your WhatsApp/SMS.',
            // Remove in production:
            'otp_dev' => config('app.debug') ? $otp : null,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $mobile = $request->mobile;
        $key = 'otp-verify:' . $mobile;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['success' => false, 'message' => 'Too many failed attempts. Try again later.'], 429);
        }

        $otpRecord = OtpVerification::where('mobile', $mobile)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json(['success' => false, 'message' => 'No OTP found. Please request a new OTP.'], 400);
        }

        if ($otpRecord->isExpired()) {
            return response()->json(['success' => false, 'message' => 'OTP has expired. Please request a new one.'], 400);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            RateLimiter::hit($key, 300);
            $otpRecord->increment('attempts');
            return response()->json(['success' => false, 'message' => 'Invalid OTP. Please try again.'], 400);
        }

        // Mark OTP verified
        $otpRecord->update(['verified_at' => now()]);
        RateLimiter::clear($key);

        // Find or create student user
        $student = Student::where('mobile', $mobile)->where('status', 'active')->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student record not found.'], 404);
        }

        $user = $student->user;
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User account not found. Contact your owner.'], 404);
        }

        Auth::guard('student')->login($user);
        if (!Auth::guard('owner')->check()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'success' => true,
            'redirect' => route('student.dashboard'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('student')->logout();
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->isStudent()) {
            Auth::guard('web')->logout();
        }

        if (!Auth::guard('owner')->check()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('student.otp');
    }
}
