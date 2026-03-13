<?php

namespace App\Console\Commands;

use App\Models\AnnexStock;
use App\Models\BackOrder;
use App\Models\BranchStock;
use App\Models\HeadquartersStock;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use App\Models\User;
use Illuminate\Console\Command;

class RefulfillInvoiceStock extends Command
{
    protected $signature = 'invoice:unapprove {invoice : The invoice ID to unapprove}';

    protected $description = 'Unapprove an invoice so it can be re-approved with correct stock deduction (for invoices approved before stock-source fix)';

    public function handle(): int
    {
        $invoiceId = (int) $this->argument('invoice');
        $invoice = Invoice::with('items', 'user.role', 'order.items')->find($invoiceId);

        if (! $invoice) {
            $this->error("Invoice {$invoiceId} not found.");

            return Command::FAILURE;
        }

        if (! $invoice->is_approved) {
            $this->error('Invoice is not approved.');

            return Command::FAILURE;
        }

        $order = Order::where('invoice_id', $invoice->id)->first();
        if (! $order) {
            $this->error('No order found for this invoice.');
            $invoice->update(['is_approved' => false, 'approved_at' => null]);

            return Command::SUCCESS;
        }

        $products = Product::where('is_active', true)->get();
        $stockSource = $this->resolveStockSource($invoice);

        \Illuminate\Support\Facades\DB::transaction(function () use ($invoice, $order, $products, $stockSource) {
            foreach ($order->items as $orderItem) {
                $product = $this->findProductForItem($products, $orderItem->product_name);
                if (! $product || (int) $orderItem->quantity <= 0) {
                    continue;
                }
                $qty = (int) round((float) $orderItem->quantity, 2);
                if ($stockSource === 'main') {
                    HeadquartersStock::decrementStock((int) $invoice->user_id, $product->id, $qty);
                    $product->increment('stock', $qty);
                } elseif ($stockSource[0] === 'hq_to_branch') {
                    $ok = BranchStock::decrementStock($stockSource[2], $product->id, $qty);
                    if (! $ok) {
                        throw new \RuntimeException("Cannot unapprove: insufficient branch stock for " . ($product->display_name ?? $product->name) . ".");
                    }
                    HeadquartersStock::incrementStock($stockSource[1], $product->id, $qty);
                } elseif ($stockSource[0] === 'branch_to_sc') {
                    $ok = ServiceCenterStock::decrementStock($stockSource[2], $product->id, $qty);
                    if (! $ok) {
                        throw new \RuntimeException("Cannot unapprove: insufficient service center stock for " . ($product->display_name ?? $product->name) . ".");
                    }
                    BranchStock::incrementStock($stockSource[1], $product->id, $qty);
                } elseif ($stockSource[0] === 'branch_to_annex') {
                    $ok = AnnexStock::decrementStock($stockSource[2], $product->id, $qty);
                    if (! $ok) {
                        throw new \RuntimeException("Cannot unapprove: insufficient annex stock for " . ($product->display_name ?? $product->name) . ".");
                    }
                    BranchStock::incrementStock($stockSource[1], $product->id, $qty);
                } elseif ($stockSource[0] === 'sc_to_annex') {
                    $ok = AnnexStock::decrementStock($stockSource[2], $product->id, $qty);
                    if (! $ok) {
                        throw new \RuntimeException("Cannot unapprove: insufficient annex stock for " . ($product->display_name ?? $product->name) . ".");
                    }
                    ServiceCenterStock::incrementStock($stockSource[1], $product->id, $qty);
                } elseif ($stockSource[0] === 'hq') {
                    HeadquartersStock::incrementStock($stockSource[1], $product->id, $qty);
                } else {
                    BranchStock::incrementStock($stockSource[1], $product->id, $qty);
                }
            }

            OrderItem::where('order_id', $order->id)->delete();
            $order->delete();
            BackOrder::where('invoice_id', $invoice->id)->delete();
            $invoice->update(['is_approved' => false, 'approved_at' => null]);
        });

        $this->info("Invoice {$invoice->invoice_number} has been unapproved. Stock has been restored. You can now approve it again.");

        return Command::SUCCESS;
    }

    /** Infer stock source from invoice customer (main | ['hq', id] | ['branch', id] | ['hq_to_branch', hqId, branchId]). */
    private function resolveStockSource(Invoice $invoice)
    {
        $customer = $invoice->user;
        if (! $customer || ! $customer->relationLoaded('role')) {
            $customer?->load('role');
        }
        $role = $customer?->role?->name ?? '';

        if ($role === 'headquarters') {
            return 'main';
        }
        if ($role === 'branch') {
            $branchId = (int) ($invoice->branch_user_id ?? $invoice->user_id);
            $hqId = (int) ($customer->created_by_user_id ?? 0);
            // HQ approved Branch: stock came from HQ, went to Branch
            return $hqId > 0 ? ['hq_to_branch', $hqId, $branchId] : ['branch', $branchId];
        }
        if ($role === 'service_center') {
            $branchId = (int) ($customer->created_by_user_id ?? 0);
            if ($branchId > 0) {
                return ['branch_to_sc', $branchId, (int) $customer->id];
            }
        }
        if ($role === 'annex') {
            $parentId = (int) ($customer->created_by_user_id ?? 0);
            if ($parentId > 0) {
                $parent = User::with('role')->find($parentId);
                if ($parent && $parent->role?->name === 'service_center') {
                    return ['sc_to_annex', $parentId, (int) $customer->id];
                }
                if ($parent && $parent->role?->name === 'branch') {
                    return ['branch_to_annex', $parentId, (int) $customer->id];
                }
            }
        }
        if (in_array($role, ['annex', 'service_center'], true)) {
            $hqId = $this->getFulfillingHqId($customer);
            if ($hqId > 0) {
                return ['hq', $hqId];
            }
            $branchId = (int) ($invoice->branch_user_id ?? 0);
            if ($branchId > 0) {
                return ['branch', $branchId];
            }
        }

        return 'main';
    }

    private function getFulfillingHqId($user): int
    {
        if (! $user || ! $user->created_by_user_id) {
            return 0;
        }
        $parent = User::with('role')->find($user->created_by_user_id);
        if (! $parent) {
            return 0;
        }
        if ($parent->role?->name === 'branch' && $parent->created_by_user_id) {
            return (int) $parent->created_by_user_id;
        }
        if ($parent->role?->name === 'service_center' && $parent->created_by_user_id) {
            $branch = User::with('role')->find($parent->created_by_user_id);

            return ($branch && $branch->role?->name === 'branch' && $branch->created_by_user_id)
                ? (int) $branch->created_by_user_id
                : 0;
        }

        return 0;
    }

    private function findProductForItem($products, string $itemName)
    {
        $itemName = trim($itemName);
        if ($itemName === '') {
            return null;
        }
        $prod = $products->first(fn ($p) => ($p->display_name ?? null) === $itemName || ($p->name ?? null) === $itemName);
        if ($prod) {
            return $prod;
        }
        $lower = strtolower($itemName);
        $prod = $products->first(fn ($p) => strtolower($p->display_name ?? '') === $lower || strtolower($p->name ?? '') === $lower);
        if ($prod) {
            return $prod;
        }
        $noParens = trim(preg_replace('/\s*\([^)]*\)/', '', $itemName));
        if ($noParens !== $itemName) {
            $prod = $products->first(fn ($p) => ($p->name ?? '') === $noParens || strtolower($p->name ?? '') === strtolower($noParens));
            if ($prod) {
                return $prod;
            }
        }

        return $products->first(fn ($p) => str_contains(strtolower($p->display_name ?? ''), $lower) || str_contains(strtolower($p->name ?? ''), $lower));
    }
}
