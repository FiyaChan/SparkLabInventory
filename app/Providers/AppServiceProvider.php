<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Applies to every Password::defaults() call across the app —
        // registration, password reset, MFA-adjacent flows, etc.
        // Change these once here rather than duplicating rules everywhere.
        Password::defaults(function () {
            $rule = Password::min(8)
                ->mixedCase()   // requires both upper and lower case
                ->numbers()
                ->symbols();

            // uncompromised(): checks the password (via a k-anonymity hash range,
            // never the full password) against the "Have I Been Pwned" breach database.
            // Only enable in production — it makes an external HTTP call, which slows
            // down local dev/testing.
            return $this->app->environment('production')
                ? $rule->uncompromised()
                : $rule;
        });
    }
}
