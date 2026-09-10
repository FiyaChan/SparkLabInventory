<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->mfa_enabled && ! $request->session()->get('mfa_authenticated', false)) {
                if ($request->routeIs('mfa.*') || $request->routeIs('logout')) {
                    return $next($request);
                }

                $userId = $user->id;
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->put('mfa_pending_user_id', $userId);

                return redirect()->route('mfa.challenge')->withErrors([
                    'code' => 'Please enter your 2FA security code to continue.',
                ]);
            }
        }

        return $next($request);
    }
}
