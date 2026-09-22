<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'mfa' => \App\Http\Middleware\EnsureMfaIsVerified::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureMfaIsVerified::class,
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'payment/toyyibpay/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

// Automatically redirect storage path to /tmp on serverless read-only filesystems (e.g. Vercel)
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || env('VERCEL') || ! is_writable($app->storagePath())) {
    $app->useStoragePath('/tmp/storage');
}

return $app;
