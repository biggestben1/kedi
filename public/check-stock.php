<?php
/**
 * Debug: verify branch stock. DELETE after use.
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

$branchStockCount = \App\Models\BranchStock::count();
$branchStockRows = \App\Models\BranchStock::with('product')->orderBy('branch_user_id')->orderBy('product_id')->get();
$branchUsers = \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'branch'))->orderBy('name')->get(['id', 'name', 'email']);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Stock Debug</title></head>
<body style="font-family:sans-serif; padding:20px;">
<h2>Branch stock debug</h2>
<p><strong>branch_stock table:</strong> <?= $branchStockCount ?> rows</p>
<h3>Branch users (Products page shows stock for the logged-in user)</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Rows in branch_stock</th></tr>
<?php foreach ($branchUsers as $bu): 
    $count = \App\Models\BranchStock::where('branch_user_id', $bu->id)->sum('quantity');
    $rowCount = \App\Models\BranchStock::where('branch_user_id', $bu->id)->count();
?>
<tr>
    <td><?= $bu->id ?></td>
    <td><?= htmlspecialchars($bu->name) ?></td>
    <td><?= htmlspecialchars($bu->email ?? '') ?></td>
    <td><?= $rowCount ?> rows (<?= $count ?> units)</td>
</tr>
<?php endforeach; ?>
</table>
<h3>All branch_stock entries</h3>
<?php if ($branchStockRows->isEmpty()): ?>
<p>No rows. Run <a href="run-backfill.php">run-backfill.php</a> first.</p>
<?php else: ?>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
<tr><th>branch_user_id</th><th>product_id</th><th>Product</th><th>quantity</th></tr>
<?php foreach ($branchStockRows as $r): ?>
<tr>
    <td><?= $r->branch_user_id ?></td>
    <td><?= $r->product_id ?></td>
    <td><?= htmlspecialchars($r->product?->name ?? '—') ?></td>
    <td><?= $r->quantity ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p><strong>Important:</strong> The Products page shows stock for the <em>logged-in user</em>. Log in as the Branch user whose ID matches branch_user_id above.</p>
<p><a href="/admin/products">Products</a> | <a href="/admin">Admin</a></p>
<p><em>Delete check-stock.php after use.</em></p>
</body>
</html>
