<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request)
    {
        // Step 1: check rate limit BEFORE touching the database or hashing anything.
        // This is cheap and stops brute-force attempts early.
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');

        // Auth::attempt() internally uses Hash::check() (bcrypt) — timing-safe comparison,
        // never a plain === match, which would be vulnerable to timing attacks.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($request->throttleKey(), 60); // lock for 60s per failed attempt

            ActivityLog::record('login.failed', ['email' => $request->input('email')]);

            throw ValidationException::withMessages([
                // Deliberately vague — never reveal whether the email exists or the
                // password was wrong. Prevents user enumeration attacks.
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate(); // prevents session fixation attacks

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'This account has been disabled. Contact an administrator.',
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        ActivityLog::record('login.success', ['user_id' => $user->id]);

        // If MFA is enabled, redirect to a challenge screen instead of the dashboard.
        // The user is authenticated but NOT yet fully "logged in" until they pass MFA.
        if ($user->mfa_enabled) {
            session(['mfa_pending_user_id' => $user->id]);
            Auth::logout();
            return redirect()->route('mfa.challenge');
        }

        session(['mfa_authenticated' => true]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        ActivityLog::record('logout', ['user_id' => Auth::id()]);

        Auth::logout();

        // Invalidate and regenerate the session/CSRF token — prevents session reuse
        // after logout (e.g. via browser back button or a stolen session cookie).
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
