<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Register in bootstrap/app.php:
 *   $middleware->alias(['role' => \App\Http\Middleware\EnsureUserHasRole::class]);
 *
 * Usage on routes:
 *   Route::middleware('role:admin')->group(...)
 *   Route::middleware('role:admin,staff')->group(...)   // multiple roles allowed
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401); // not authenticated at all
        }

        if (! $user->hasAnyRole($roles)) {
            // Security-relevant event: log it. Repeated hits on this from the
            // same user/IP is exactly what your "Security Alerts" widget should surface.
            ActivityLog::record('access.denied', [
                'user_id' => $user->id,
                'required_roles' => $roles,
                'route' => $request->route()?->getName(),
            ]);

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
