<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class MfaController extends Controller
{
    /**
     * Shown right after a successful password check, if the user has MFA enabled.
     */
    public function challenge()
    {
        if (! session('mfa_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.mfa-challenge');
    }

    /**
     * Verify the 6-digit TOTP code during the login flow.
     */
    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $userId = session('mfa_pending_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $throttleKey = 'mfa-verify|' . $userId . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            ActivityLog::record('mfa.rate_limited', ['user_id' => $userId]);

            throw ValidationException::withMessages([
                'code' => "Too many invalid authentication attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::findOrFail($userId);
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->mfa_secret, $request->input('code'));

        if (! $valid) {
            RateLimiter::hit($throttleKey, 60);
            ActivityLog::record('mfa.failed', ['user_id' => $user->id]);

            return back()->withErrors(['code' => 'Invalid 6-digit authentication code. Please try again.']);
        }

        RateLimiter::clear($throttleKey);
        session()->forget('mfa_pending_user_id');

        Auth::login($user);
        $request->session()->regenerate();
        session(['mfa_authenticated' => true]);

        ActivityLog::record('mfa.success', ['user_id' => $user->id]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Setup screen: generates a secret + native SVG QR code to scan with Google Authenticator / Authy.
     */
    public function setup(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Store temporarily in session until the user confirms with a valid code
        session(['mfa_setup_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'SparkLab Kids Science'),
            $user->email,
            $secret
        );

        // Generate inline SVG QR Code using BaconQrCode
        $renderer = new ImageRenderer(
            new RendererStyle(220, 0),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return view('auth.mfa-setup', compact('qrCodeSvg', 'secret'));
    }

    /**
     * Confirm initial MFA setup by verifying the first 6-digit code.
     */
    public function confirmSetup(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $secret = session('mfa_setup_secret');
        $google2fa = new Google2FA();

        if (! $secret || ! $google2fa->verifyKey($secret, $request->input('code'))) {
            return back()->withErrors(['code' => 'Invalid code. Please scan the QR code and try again.']);
        }

        $user = $request->user();
        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_enabled' => true,
        ])->save();

        session()->forget('mfa_setup_secret');
        session(['mfa_authenticated' => true]);

        ActivityLog::record('mfa.enabled', ['user_id' => $user->id]);

        return redirect()->route('profile.edit')->with('status', 'Two-Factor Authentication (2FA) has been successfully enabled.');
    }

    /**
     * Disable MFA with password confirmation.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Incorrect password. Unable to disable 2FA.']);
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_enabled' => false,
        ])->save();

        session()->forget('mfa_authenticated');

        ActivityLog::record('mfa.disabled', ['user_id' => $user->id]);

        return redirect()->route('profile.edit')->with('status', 'Two-Factor Authentication (2FA) has been disabled.');
    }
}
