<?php
/**
 * Pre-check: why might backfill do nothing? DELETE after use.
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

$approvedBranchInvoices = \App\Models\Invoice::with(['user.role', 'order'])
    ->where('is_approved', true)
    ->whereHas('user', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'branch')))
    ->get();

$withOrder = $approvedBranchInvoices->filter(fn ($i) => $i->order)->values();
$withoutOrder = $approvedBranchInvoices->filter(fn ($i) => !$i->order)->values();

$hqStockTotal = \App\Models\HeadquartersStock::sum('quantity');
$branchStockTotal = \App\Models\BranchStock::sum('quantity');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Backfill Status</title></head>
<body style="font-family:sans-serif; padding:20px;">
<h2>Backfill pre-check</h2>
<p><strong>Approved Branch invoices:</strong> <?= $approvedBranchInvoices->count() ?></p>
<p><strong>With Order (can be backfilled):</strong> <?= $withOrder->count() ?></p>
<p><strong>Without Order (skipped):</strong> <?= $withoutOrder->count() ?></p>
<p><strong>headquarters_stock total units:</strong> <?= $hqStockTotal ?></p>
<p><strong>branch_stock total units:</strong> <?= $branchStockTotal ?></p>

<?php if ($withOrder->isEmpty()): ?>
<h3 style="color:#c00;">No invoices to backfill</h3>
<p>Backfill only works for invoices that are: (1) approved, (2) customer has role "branch", (3) have an associated Order.</p>
<?php if ($withoutOrder->isNotEmpty()): ?>
<p>You have <?= $withoutOrder->count() ?> approved Branch invoice(s) but they have no Order. These were likely "Move to dispatch" instead of "Approve" – the Approve button creates the Order and moves stock.</p>
<?php endif; ?>
<?php else: ?>
<h3>Invoices that can be backfilled</h3>
<table border="1" cellpadding="6" style="border-collapse:collapse;">
<tr><th>Invoice</th><th>Branch (user_id)</th><th>HQ (created_by)</th><th>Order ID</th><th>Items</th><th>Will backfill?</th></tr>
<?php foreach ($withOrder as $inv):
    $branchUser = $inv->user;
    $hqId = (int)($branchUser->created_by_user_id ?? 0);
    $items = $inv->order ? $inv->order->items->count() : 0;
    $canBackfill = $hqId > 0;
?>
<tr>
    <td><?= htmlspecialchars($inv->invoice_number) ?></td>
    <td><?= $inv->user_id ?> (<?= htmlspecialchars($branchUser->name ?? '') ?>)</td>
    <td><?= $hqId ?: '<span style="color:red;">MISSING</span>' ?></td>
    <td><?= $inv->order?->id ?? '—' ?></td>
    <td><?= $items ?></td>
    <td><?= $canBackfill ? 'Yes' : '<span style="color:red;">No – Branch must have HQ</span>' ?></td>
</tr>
<?php endforeach; ?>
</table>
<p>
    <a href="run-backfill.php?dry_run=1">Dry run</a> |
    <a href="run-backfill.php?dry_run=1&from_main=1">Dry run (from main)</a> |
    <a href="run-backfill.php">Apply</a> |
    <a href="run-backfill.php?from_main=1">Apply (from main)</a>
</p>
<p><small><strong>from main</strong> = use main warehouse (products.stock) when HQ has 0 in headquarters_stock</small></p>
<?php endif; ?>
<p><em>Delete backfill-status.php after use.</em></p>
</body>
</html>
