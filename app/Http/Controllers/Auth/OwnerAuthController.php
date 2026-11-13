<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class OwnerAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $initialTab = $request->query('tab') === 'tenant' ? 'tenant' : 'owner';
        $webUser = Auth::guard('web')->user();

        if ($initialTab === 'tenant') {
            if (Auth::guard('student')->check() || ($webUser && $webUser->isStudent())) {
                return redirect()->route('student.dashboard');
            }
        } else {
            if (Auth::guard('owner')->check() || ($webUser && $webUser->isOwner())) {
                return redirect()->route('owner.dashboard');
            }
        }

        return response()
            ->view('auth.owner-login', compact('initialTab'))
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->where('role', 'owner')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        Auth::guard('owner')->login($user, $request->boolean('remember'));
        if (!Auth::guard('student')->check()) {
            $request->session()->regenerate();
        }

        return redirect()->route('owner.dashboard');
    }

    public function showRegister()
    {
        return response()
            ->view('auth.owner-register')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'mobile' => 'required|string|max:15|unique:users',
            'business_type' => 'nullable|in:pg,hostel,lodge',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'role' => 'owner',
            'business_type' => $request->input('business_type', 'pg'),
            'password' => Hash::make($request->password),
        ]);

        Auth::guard('owner')->login($user);
        if (!Auth::guard('student')->check()) {
            $request->session()->regenerate();
        }
        return redirect()->route('owner.dashboard')->with('success', 'Welcome to Niwasthal!');
    }

    public function logout(Request $request)
    {
        Auth::guard('owner')->logout();
        if (Auth::guard('web')->check() && Auth::guard('web')->user()->isOwner()) {
            Auth::guard('web')->logout();
        }

        if (!Auth::guard('student')->check()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
