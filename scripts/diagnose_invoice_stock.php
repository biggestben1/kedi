<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\Product;

$invoiceId = $argv[1] ?? null;
if (! $invoiceId) {
    echo "Usage: php scripts/diagnose_invoice_stock.php <invoice_id>\n";
    exit(1);
}

$invoice = Invoice::with('items')->find($invoiceId);
if (! $invoice) {
    echo "Invoice {$invoiceId} not found.\n";
    exit(1);
}

$products = Product::where('is_active', true)->get();

function findProductForItem($products, $itemName)
{
    $itemName = trim((string) $itemName);
    if ($itemName === '') return null;
    $prod = $products->first(fn($p) => ($p->display_name ?? null) === $itemName);
    if ($prod) return $prod;
    $prod = $products->first(fn($p) => ($p->name ?? null) === $itemName);
    if ($prod) return $prod;
    $prod = $products->first(fn($p) => isset($p->item_code) && $p->item_code === $itemName);
    if ($prod) return $prod;
    $lower = strtolower($itemName);
    $prod = $products->first(fn($p) => strtolower($p->display_name ?? '') === $lower || strtolower($p->name ?? '') === $lower || (isset($p->item_code) && strtolower($p->item_code) === $lower));
    if ($prod) return $prod;
    $noParens = trim(preg_replace('/\s*\([^)]*\)/', '', $itemName));
    if ($noParens !== $itemName) {
        $prod = $products->first(fn($p) => ($p->display_name ?? '') === $noParens || ($p->name ?? '') === $noParens || strtolower($p->name ?? '') === strtolower($noParens));
        if ($prod) return $prod;
    }
    $prod = $products->first(fn($p) => str_contains(strtolower($p->display_name ?? ''), $lower) || str_contains(strtolower($p->name ?? ''), $lower));
    return $prod ?: null;
}

echo "Invoice {$invoice->id} - {$invoice->invoice_number}\n";
echo str_repeat('=', 60) . "\n";
foreach ($invoice->items as $index => $item) {
    $matched = findProductForItem($products, $item->item_name);
    echo sprintf("Item %d: %s\n", $index + 1, $item->item_name);
    echo sprintf("  Quantity: %s\n", $item->quantity);
    if ($matched) {
        echo sprintf("  Matched Product ID: %d\n", $matched->id);
        echo sprintf("  Product name: %s\n", $matched->name);
        echo sprintf("  Display name: %s\n", $matched->display_name ?? $matched->name);
        echo sprintf("  Item code: %s\n", $matched->item_code ?? '');
        echo sprintf("  Stock (main): %d\n", (int) $matched->stock);
    } else {
        echo "  Matched Product: NONE\n";
        // Try DB lookup by LIKE
        $byName = Product::where('name', 'like', "%{$item->item_name}%")->orWhere('item_code', 'like', "%{$item->item_name}%")->limit(5)->get();
        if ($byName->isNotEmpty()) {
            echo "  Close matches:\n";
            foreach ($byName as $p) {
                echo sprintf("    - ID %d: %s (stock: %d, code: %s)\n", $p->id, $p->name, (int)$p->stock, $p->item_code ?? '');
            }
        }
    }
    echo str_repeat('-', 60) . "\n";
}

echo "Done.\n";

return 0;
