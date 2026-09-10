<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * EmailVerificationRequest automatically validates the signed URL's hash
     * and expiry — Laravel rejects tampered or expired links before this
     * method even runs. This is what stops an attacker from forging a
     * verification link for someone else's account.
     */
    public function __invoke(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->fulfill(); // marks email_verified_at and fires the Verified event

        ActivityLog::record('email.verified', ['user_id' => $request->user()->id]);

        return redirect()->route('dashboard')->with('status', 'Email verified successfully!');
    }
}