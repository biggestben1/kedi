<?php
/**
 * One-time branch stock backfill. Visit https://optimalconsult.org/run-backfill.php
 * Use ?dry_run=1 to preview. DELETE THIS FILE after use.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $current = __DIR__;
    $candidates = [
        $current,
        dirname($current),
        $current . '/../my-laravel-app',
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
        die('Laravel not found.');
    }

    define('LARAVEL_START', microtime(true));
    require $base . '/vendor/autoload.php';
    $app = require_once $base . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === '1';
    $fromMain = isset($_GET['from_main']) && $_GET['from_main'] === '1';
    \Illuminate\Support\Facades\Artisan::call('stock:backfill-branch', [
        '--dry-run' => $dryRun,
        '--from-main' => $fromMain,
    ]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    echo '<pre>' . htmlspecialchars($output) . '</pre>';
    if ($dryRun) {
        echo '<p>Run without <code>?dry_run=1</code> to apply. Add <code>?from_main=1</code> to use main warehouse when HQ has 0 stock.</p>';
    } elseif ($fromMain) {
        echo '<p>Used main warehouse for products where HQ had 0 stock.</p>';
    }
    echo '<p><strong>DELETE run-backfill.php after use!</strong></p>';
} catch (Throwable $e) {
    echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . "\n" . $e->getTraceAsString() . '</pre>';
}
