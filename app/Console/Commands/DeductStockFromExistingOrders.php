<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeductStockFromExistingOrders extends Command
{
    protected $signature = 'orders:deduct-stock {--dry-run : Show what would be deducted without actually doing it}';
    protected $description = 'Deduct stock from existing paid orders that were created before stock deduction was implemented';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        // Get all paid orders (excluding drafts and cancelled)
        $orders = Order::whereIn('status', ['paid', 'packed', 'shipped', 'delivered', 'completed'])
            ->with('items')
            ->orderBy('id')
            ->get();

        $this->info("Found {$orders->count()} paid orders to process");

        $deducted = [];
        $errors = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // Skip invoice items (they don't map to products)
                if (str_starts_with($item->item_code, 'INV-')) {
                    continue;
                }

                // Find product by item_code
                $product = Product::where('item_code', $item->item_code)->first();
                
                if (!$product) {
                    $errors[] = "Order #{$order->id}: Product not found for item_code '{$item->item_code}'";
                    continue;
                }

                // Check current stock
                $currentStock = $product->stock;
                $quantity = $item->quantity;

                if ($dryRun) {
                    $this->line("Would deduct {$quantity} from {$product->name} (Item: {$item->item_code}, Current stock: {$currentStock})");
                } else {
                    // Deduct stock
                    DB::beginTransaction();
                    try {
                        $product->decrement('stock', $quantity);
                        $deducted[] = [
                            'order_id' => $order->id,
                            'product' => $product->name,
                            'item_code' => $item->item_code,
                            'quantity' => $quantity,
                            'old_stock' => $currentStock,
                            'new_stock' => $product->fresh()->stock,
                        ];
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $errors[] = "Order #{$order->id}: Failed to deduct stock for {$product->name}: " . $e->getMessage();
                    }
                }
            }
        }

        if ($dryRun) {
            $this->info("\nDRY RUN COMPLETE - No changes were made");
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("\nStock deduction complete!");
            $this->info("Deducted stock from " . count($deducted) . " order items");
            
            if (!empty($deducted)) {
                $this->table(
                    ['Order ID', 'Product', 'Item Code', 'Quantity', 'Old Stock', 'New Stock'],
                    array_map(fn($d) => [
                        $d['order_id'],
                        $d['product'],
                        $d['item_code'],
                        $d['quantity'],
                        $d['old_stock'],
                        $d['new_stock'],
                    ], $deducted)
                );
            }
        }

        if (!empty($errors)) {
            $this->error("\nErrors encountered:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }
}
