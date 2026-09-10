<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------
// GUEST ROUTES — only accessible when NOT logged in
// ---------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');

    // throttle:6,1 = 6 attempts per minute at the ROUTE level, on top of the
    // per-email+IP RateLimiter logic inside LoginRequest. Defense in depth:
    // even if one layer is misconfigured, the other still limits brute force.
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1') // strict limit — password reset emails are a common abuse vector
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge');
    Route::post('mfa/challenge', [MfaController::class, 'verify'])
        ->middleware('throttle:5,1')
        ->name('mfa.verify');
});

// ---------------------------------------------------------------------
// AUTHENTICATED ROUTES — must be logged in
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    // 'signed' = validates the URL hasn't been tampered with (checks the hash param)
    // 'throttle:6,1' = limits how fast a verification link can be (re)used
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('mfa/setup', [MfaController::class, 'setup'])->name('mfa.setup');
    Route::post('mfa/setup', [MfaController::class, 'confirmSetup'])->name('mfa.confirm');
    Route::post('mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');
});

// ---------------------------------------------------------------------
// ROLE-PROTECTED EXAMPLE — how future modules will lock down routes
// ---------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->hasAnyRole(['admin', 'staff'])) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('shop.index');
    })->name('dashboard');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Product/Inventory/User-management routes
});

Route::middleware(['auth', 'role:admin|staff'])->prefix('staff')->group(function () {
    // Staff-accessible operational routes (stock in/out, order status updates)
});
