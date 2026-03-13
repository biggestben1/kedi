<?php
/**
 * Allocate same stock as hbranch ( branch #12) to Matthew branch (user 18).
 * Uses main warehouse. DELETE after use.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$candidates = [__DIR__, dirname(__DIR__), __DIR__ . '/../my-laravel-app', dirname(__DIR__) . '/my-laravel-app'];
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

$sourceBranchId = 12; // hbranch
$targetBranchId = 18; // Matthew branch

$sourceStock = \App\Models\BranchStock::with('product')
    ->where('branch_user_id', $sourceBranchId)
    ->where('quantity', '>', 0)
    ->get();

if ($sourceStock->isEmpty()) {
    die('No stock found for branch #' . $sourceBranchId . ' to copy from.');
}

$totalAdded = 0;
$output = [];
$errors = [];

foreach ($sourceStock as $row) {
    $product = $row->product;
    $qty = (int) $row->quantity;
    if (!$product || $qty <= 0) {
        continue;
    }

    $product->refresh();
    $mainAvail = (int) $product->stock;
    if ($mainAvail < $qty) {
        $errors[] = "{$product->display_name}: need {$qty}, main has {$mainAvail}";
        continue;
    }

    if (!$dryRun) {
        $product->decrement('stock', $qty);
        \App\Models\BranchStock::incrementStock($targetBranchId, $product->id, $qty);
    }
    $output[] = "{$product->display_name}: +{$qty} to branch #{$targetBranchId}";
    $totalAdded += $qty;
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre>';
if ($dryRun) {
    echo "DRY RUN - no changes made\n\n";
}
foreach ($output as $line) {
    echo $line . "\n";
}
if (!empty($errors)) {
    echo "\nSkipped (insufficient main stock):\n";
    foreach ($errors as $e) {
        echo "  " . $e . "\n";
    }
}
echo "\n" . ($dryRun ? "Would add {$totalAdded} units. " : "Added {$totalAdded} units. ");
echo $dryRun ? '<a href="?">Apply</a>' : 'Done.';
echo '</pre>';
echo '<p><strong>DELETE allocate-matthew-branch.php after use!</strong></p>';
