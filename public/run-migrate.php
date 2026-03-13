<?php
/**
 * One-time migration runner. Visit https://optimalconsult.org/run-migrate.php
 * DELETE THIS FILE immediately after use.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $current = __DIR__;
    $candidates = [
        $current,                          // doc root = project root
        dirname($current),                  // doc root = public/
        $current . '/../my-laravel-app',    // doc root + sibling my-laravel-app (matches index.php)
        dirname($current) . '/my-laravel-app',
    ];
    $base = null;
    foreach ($candidates as $dir) {
        if (is_file($dir . '/vendor/autoload.php') && is_file($dir . '/bootstrap/app.php')) {
            $base = $dir;
            break;
        }
    }
    if (!$base) {
        die('Laravel not found. Checked: ' . htmlspecialchars(implode(', ', $candidates)));
    }

    define('LARAVEL_START', microtime(true));
    require $base . '/vendor/autoload.php';
    $app = require_once $base . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo 'Migrations completed. DELETE this file now!';
} catch (Throwable $e) {
    echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . "\n";
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . '</pre>';
}
