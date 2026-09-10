<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        // Laravel's Password::sendResetLink() generates a signed, time-limited token
        // (default 60 min expiry) and emails it — the token itself is hashed in the DB,
        // so even a leaked `password_reset_tokens` table can't be used to reset accounts.
        $status = Password::sendResetLink($request->only('email'));

        // Always show the same generic message regardless of whether the email exists —
        // prevents attackers from using this form to enumerate registered accounts.
        return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
    }
}