<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

$invoiceId = $argv[1] ?? null;
$confirm = $argv[2] ?? null;

if (! $invoiceId) {
    echo "Usage: php scripts/set_invoice_products_stock.php <invoice_id> --yes\n";
    exit(1);
}

if ($confirm !== '--yes') {
    echo "This will set stock=100 for products referenced by invoice {$invoiceId}.\n";
    echo "Run: php scripts/set_invoice_products_stock.php {$invoiceId} --yes\n";
    exit(1);
}

$invoice = Invoice::with('items')->find($invoiceId);
if (! $invoice) {
    echo "Invoice {$invoiceId} not found.\n";
    exit(1);
}

$itemCount = 0;
$updated = 0;

try {
    DB::transaction(function () use ($invoice, &$itemCount, &$updated) {
        foreach ($invoice->items as $item) {
            $itemCount++;
            // Try find product by item_name or item_code if present
            $product = Product::where('item_code', $item->item_name)
                ->orWhere('name', $item->item_name)
                ->first();

            if (! $product) {
                // skip if not found
                continue;
            }

            $product->stock = 100;
            $product->save();
            $updated++;
        }
    });

    echo "Invoice {$invoice->id}: processed {$itemCount} item(s), updated {$updated} product(s) to stock=100\n";
} catch (Throwable $e) {
    echo "Failed to update products for invoice {$invoiceId}: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
