<?php

namespace App\Console\Commands;

use App\Models\BranchStock;
use App\Models\HeadquartersStock;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Console\Command;

class BackfillBranchStock extends Command
{
    protected $signature = 'stock:backfill-branch
        {--dry-run : Show what would be done without making changes}
        {--from-main : Use main warehouse (products.stock) when HQ has 0 stock}';

    protected $description = 'Backfill branch_stock from approved Branch invoices that never received stock (fix for invoices approved before HQ->Branch flow was fixed)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fromMain = (bool) $this->option('from-main');
        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be made');
        }
        if ($fromMain) {
            $this->info('Using main warehouse when HQ has 0 stock.');
        }

        $products = Product::where('is_active', true)->get();

        $invoices = Invoice::with(['user.role', 'order.items'])
            ->where('is_approved', true)
            ->whereHas('user', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'branch')))
            ->whereHas('order')
            ->get();

        if ($invoices->isEmpty()) {
            $this->warn('No approved Branch invoices with orders found.');
            $this->line('Requirements: Invoice must be approved (is_approved=1), customer must have role=branch, and invoice must have an associated Order.');
            $approvedBranchCount = Invoice::where('is_approved', true)
                ->whereHas('user', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'branch')))
                ->count();
            $withOrderCount = Invoice::where('is_approved', true)
                ->whereHas('user', fn ($q) => $q->whereHas('role', fn ($r) => $r->where('name', 'branch')))
                ->whereHas('order')
                ->count();
            $this->line("Approved Branch invoices: {$approvedBranchCount}. Of those with Order: {$withOrderCount}.");

            return Command::SUCCESS;
        }

        $this->info('Found ' . $invoices->count() . ' approved Branch invoice(s) with orders.');

        $totalAdded = 0;
        foreach ($invoices as $invoice) {
            $branchUser = $invoice->user;
            $branchUserId = (int) ($invoice->branch_user_id ?? $invoice->user_id);
            $hqUserId = (int) ($branchUser->created_by_user_id ?? 0);
            if ($hqUserId <= 0) {
                $this->warn("Invoice {$invoice->invoice_number}: Branch has no HQ (created_by_user_id). Skipping.");
                continue;
            }

            $order = $invoice->order;
            $this->info("Processing invoice {$invoice->invoice_number} (branch #{$branchUserId}, HQ #{$hqUserId})...");
            foreach ($order->items as $item) {
                $product = $this->findProduct($products, $item->product_name);
                if (! $product) {
                    $this->warn("  Product not found: {$item->product_name}");
                    continue;
                }
                $qty = (int) round((float) $item->quantity, 2);
                if ($qty <= 0) {
                    continue;
                }

                $hqAvail = HeadquartersStock::getQuantity($hqUserId, $product->id);
                $product->refresh();
                $mainAvail = (int) $product->stock;
                $useMain = false;

                if ($hqAvail >= $qty) {
                    $source = "HQ #{$hqUserId}";
                } elseif ($fromMain && $mainAvail >= $qty) {
                    $useMain = true;
                    $source = 'main warehouse';
                } elseif ($fromMain && $mainAvail > 0) {
                    $this->warn("  Insufficient main stock for {$product->display_name}: need {$qty}, has {$mainAvail}. Skipping.");
                    continue;
                } else {
                    $this->warn("  Insufficient HQ stock for {$product->display_name}: need {$qty}, has {$hqAvail}. Skipping." . ($fromMain ? '' : ' Use --from-main to try main warehouse.'));
                    continue;
                }

                if (! $dryRun) {
                    if ($useMain) {
                        $product->decrement('stock', $qty);
                    } else {
                        HeadquartersStock::decrementStock($hqUserId, $product->id, $qty);
                    }
                    BranchStock::incrementStock($branchUserId, $product->id, $qty);
                }
                $this->line("  {$product->display_name}: +{$qty} to branch #{$branchUserId} (from {$source})");
                $totalAdded += $qty;
            }
        }

        $this->info($dryRun
            ? "Would add {$totalAdded} units to branch stock. Run without --dry-run to apply."
            : "Added {$totalAdded} units to branch stock.");

        return Command::SUCCESS;
    }

    private function findProduct($products, string $itemName): ?Product
    {
        $itemName = trim($itemName);
        if ($itemName === '') {
            return null;
        }
        $p = $products->first(fn ($p) => ($p->display_name ?? $p->name) === $itemName);
        if ($p) {
            return $p;
        }
        $lower = strtolower($itemName);
        $p = $products->first(fn ($p) => strtolower($p->display_name ?? '') === $lower || strtolower($p->name ?? '') === $lower);
        if ($p) {
            return $p;
        }
        $noParens = trim(preg_replace('/\s*\([^)]*\)/', '', $itemName));
        if ($noParens !== $itemName) {
            return $this->findProduct($products, $noParens);
        }
        return $products->first(
            fn ($p) => str_contains(strtolower($p->display_name ?? ''), $lower) || str_contains(strtolower($p->name ?? ''), $lower)
        );
    }
}
