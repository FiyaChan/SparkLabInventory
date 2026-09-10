<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailVerificationNotificationController extends Controller
{
    // "Resend verification email" button hits this endpoint
    public function store(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
            ActivityLog::record('email_verification.sent', ['user_id' => $request->user()->id]);
            return back()->with('status', 'verification-link-sent');
        } catch (\Throwable $e) {
            Log::error('Resending verification email failed: '.$e->getMessage(), [
                'user_id' => $request->user()->id,
            ]);
            return back()->with('error', 'Unable to send verification email. Please check your mail server configuration or try again shortly.');
        }
    }
}
