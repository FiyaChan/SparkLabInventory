<?php

/**
 * Vercel Serverless Entry Point for Laravel
 */

// 1. Prepare temporary storage directory tree on /tmp (Vercel filesystem is read-only)
$tmpDirs = [
    '/tmp/storage/app/public',
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/cache',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($tmpDirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Set serverless environment variables
$envOverrides = [
    'VERCEL' => '1',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/storage/framework/views',
    'LOG_CHANNEL' => 'stderr',
    'CACHE_STORE' => 'array',
];

foreach ($envOverrides as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// 3. Fallback SQLite in /tmp for standalone demonstration
if (getenv('DB_CONNECTION') === 'sqlite' || ! getenv('DB_CONNECTION') || (isset($_ENV['DB_CONNECTION']) && $_ENV['DB_CONNECTION'] === 'sqlite')) {
    $sourceSqlite = __DIR__ . '/../database/database.sqlite';
    $targetSqlite = '/tmp/database.sqlite';

    if (file_exists($sourceSqlite) && ! file_exists($targetSqlite)) {
        @copy($sourceSqlite, $targetSqlite);
    }
    
    if (file_exists($targetSqlite)) {
        putenv("DB_DATABASE={$targetSqlite}");
        $_ENV['DB_DATABASE'] = $targetSqlite;
        $_SERVER['DB_DATABASE'] = $targetSqlite;
    }
}

// 4. Forward execution to Laravel's public index
require __DIR__ . '/../public/index.php';
