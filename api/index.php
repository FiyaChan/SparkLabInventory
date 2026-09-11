<?php

/**
 * Vercel Serverless Entry Point for Laravel
 */

// 1. Prepare temporary directories for serverless environment (Vercel filesystem is read-only except /tmp)
$tmpDirs = [
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

// 2. Set serverless environment cache paths
putenv('APP_CONFIG_CACHE=/tmp/config.php');
putenv('APP_EVENTS_CACHE=/tmp/events.php');
putenv('APP_PACKAGES_CACHE=/tmp/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
putenv('APP_SERVICES_CACHE=/tmp/services.php');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('LOG_CHANNEL=stderr');

// 3. Fallback SQLite in /tmp for standalone demonstration if no remote DB is configured
if (getenv('DB_CONNECTION') === 'sqlite' || ! getenv('DB_CONNECTION')) {
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
