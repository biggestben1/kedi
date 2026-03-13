<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

$confirm = $argv[1] ?? null;
if ($confirm !== '--yes') {
    echo "This will set stock=100 for ALL products.\n";
    echo "Run: php scripts/set_all_product_stock.php --yes\n";
    exit(1);
}

try {
    $count = DB::transaction(function () {
        return Product::query()->update(['stock' => 100]);
    });

    echo "Updated {$count} product(s) to stock=100\n";
} catch (Throwable $e) {
    echo "Failed to update products: " . $e->getMessage() . "\n";
    exit(1);
}

return 0;
