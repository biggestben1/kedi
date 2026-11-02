<?php

namespace App\Http\Controllers;

use App\Models\CollectionInvoice;
use App\Models\KdCustomer;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\OrgUserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Invoices that skip the stock check and are pushed straight to a collection center.
 * Saving creates a walk-in "pay at collection" order for the chosen branch; the branch
 * deducts its own stock when it marks the order collected.
 */
class CollectionInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $this->visibleQuery($request->user())
            ->with(['user', 'items', 'order.collectionBranch', 'order.orderGroup'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('collection-invoices.index', $this->viewData($request, 'Invoice', [
            'invoices' => $invoices,
        ]));
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $productOptions = Product::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => ! empty($product->item_code))
            ->map(fn (Product $product) => [
                'name' => $product->display_name,
                'aliases' => array_values(array_filter([$product->name, $product->item_code])),
                'price' => $product->getPriceForUser($user),
            ])
            ->values();

        return view('collection-invoices.create', $this->viewData($request, 'New Invoice', [
            'productOptions' => $productOptions,
            'collectionBranches' => $this->branchesQuery()->orderBy('name')->get(['id', 'name', 'phone']),
            'openOrderGroups' => OrderGroup::where('user_id', $user->id)
                ->where('status', OrderGroup::STATUS_OPEN)
                ->orderByDesc('id')
                ->get(),
        ]));
    }

    public function store(Request $request)
    {
        $request->merge([
            'items' => array_values(array_filter(
                (array) $request->input('items', []),
                fn ($row) => is_array($row) && trim((string) ($row['item_name'] ?? '')) !== ''
            )),
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'kd_id' => ['nullable', 'string', 'max:100'],
            'order_group_id' => ['nullable', 'integer'],
            'collection_branch_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $branch = $this->branchesQuery()->find($data['collection_branch_id']);
        if (! $branch) {
            return back()->withErrors(['collection_branch_id' => 'Choose a valid collection center.'])->withInput();
        }

        $kdId = strtoupper(trim((string) ($data['kd_id'] ?? '')));
        $group = null;
        $groupId = (int) ($data['order_group_id'] ?? 0);
        if ($groupId > 0) {
            $group = OrderGroup::where('id', $groupId)
                ->where('user_id', $request->user()->id)
                ->where('status', OrderGroup::STATUS_OPEN)
                ->first();
            if (! $group) {
                return back()->withErrors(['order_group_id' => 'Choose an open order group.'])->withInput();
            }
            if ($kdId === '') {
                return back()->withErrors(['kd_id' => 'Enter a Kedi number before adding this invoice to a group.'])->withInput();
            }
        }
        if ($kdId !== '') {
            $existingKd = KdCustomer::query()
                ->whereRaw("UPPER(REPLACE(kd_no, ' ', '')) = ?", [$kdId])
                ->first();
            if ($existingKd) {
                $kdId = strtoupper(trim((string) $existingKd->kd_no));
            } else {
                KdCustomer::create([
                    'kd_no' => $kdId,
                    'customer_name' => $data['customer_name'],
                    'user_id' => $request->user()->id,
                ]);
            }
        }

        $products = Product::where('is_active', true)->get();
        $lines = [];
        foreach (array_values($data['items']) as $row) {
            $name = trim($row['item_name']);
            $quantity = (int) $row['quantity'];
            $unitPrice = round((float) $row['unit_price'], 2);
            $lines[] = [
                'item_name' => $name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
                'product' => $this->matchProduct($products, $name),
            ];
        }
        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $user = $request->user();

        $invoice = DB::transaction(function () use ($data, $lines, $subtotal, $branch, $user, $kdId, $group) {
            $invoice = CollectionInvoice::create([
                'user_id' => $user->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);
            $invoice->update(['invoice_number' => CollectionInvoice::numberFor($invoice->id)]);

            $notes = 'Invoice '.$invoice->invoice_number;
            if (! empty($data['notes'])) {
                $notes .= ' — '.$data['notes'];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_group_id' => $group?->id,
                'collection_branch_id' => $branch->id,
                'kd_id' => $kdId !== '' ? $kdId : null,
                'customer_name' => $data['customer_name'],
                'delivery_type' => Order::DELIVERY_WALK_IN,
                'invoice_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'total_bv' => 0,
                'total_pv' => 0,
                'payment_method' => Order::PAYMENT_PAY_ON_DELIVERY,
                'status' => $group ? Order::STATUS_DRAFT : Order::STATUS_PENDING,
                'shipping_address' => 'Walk-in (Pick up)',
                'shipping_phone' => $data['customer_phone'] ?? null,
                'notes' => $notes,
            ]);

            $totalBv = 0;
            $totalPv = 0;
            foreach ($lines as $index => $line) {
                /** @var Product|null $product */
                $product = $line['product'];

                $invoice->items()->create([
                    'product_id' => $product?->id,
                    'item_name' => $line['item_name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $product
                        ? $product->item_code
                        : CollectionInvoice::NON_STOCK_ITEM_PREFIX.$invoice->id.'-'.($index + 1),
                    'product_name' => $line['item_name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'bv' => $product?->bv ?? 0,
                    'pv' => $product?->pv ?? 0,
                ]);

                $totalBv += (float) ($product?->bv ?? 0) * $line['quantity'];
                $totalPv += (float) ($product?->pv ?? 0) * $line['quantity'];
            }

            $order->update(['total_bv' => $totalBv, 'total_pv' => $totalPv]);
            $invoice->update(['order_id' => $order->id]);
            if ($group) {
                $group->update([
                    'total_amount' => (float) $group->draftOrders()->sum('subtotal'),
                    'collection_branch_id' => $group->collection_branch_id ?: $branch->id,
                ]);
            }

            return $invoice;
        });

        if ($group) {
            return redirect()
                ->route('order-groups.show', $group)
                ->with('success', 'Invoice '.$invoice->invoice_number.' added to group "'.$group->displayName().'". It goes to '.$branch->name.' when the group is paid.');
        }

        return redirect()
            ->route('collection-invoices.show', $invoice)
            ->with('success', 'Invoice '.$invoice->invoice_number.' sent to '.$branch->name.' for collection.');
    }

    public function show(Request $request, CollectionInvoice $collectionInvoice)
    {
        $this->authorizeInvoice($request->user(), $collectionInvoice);
        $collectionInvoice->load(['user', 'items.product', 'order.collectionBranch', 'order.orderGroup']);

        return view('collection-invoices.show', $this->viewData($request, 'Invoice '.$collectionInvoice->invoice_number, [
            'invoice' => $collectionInvoice,
        ]));
    }

    public function destroy(Request $request, CollectionInvoice $collectionInvoice)
    {
        $this->authorizeInvoice($request->user(), $collectionInvoice);
        $collectionInvoice->load('order');

        if ($collectionInvoice->isCollected()) {
            return back()->with('error', 'This invoice has already been collected and cannot be deleted.');
        }

        DB::transaction(function () use ($collectionInvoice) {
            $collectionInvoice->order?->delete();
            $collectionInvoice->delete();
        });

        return redirect()
            ->route('collection-invoices.index')
            ->with('success', 'Invoice '.$collectionInvoice->invoice_number.' deleted and removed from the collection center.');
    }

    private function viewData(Request $request, string $title, array $data): array
    {
        return array_merge($data, [
            'layout' => $request->user()->isAdmin() ? 'layouts.admin' : 'layouts.customer',
            'pageTitle' => $title,
            'customerMenuActive' => 'collection-invoices',
        ]);
    }

    private function visibleQuery(User $user): Builder
    {
        $query = CollectionInvoice::query();
        $user->loadMissing('role');

        if ($user->isSuperAdmin() || $user->role?->name === Role::WHOLESALE_STAFF) {
            return $query;
        }

        return $query->whereIn('user_id', OrgUserScope::allowedUserIds($user) ?? [$user->id]);
    }

    private function authorizeInvoice(User $user, CollectionInvoice $invoice): void
    {
        abort_unless($this->visibleQuery($user)->whereKey($invoice->id)->exists(), 403);
    }

    private function branchesQuery(): Builder
    {
        return User::whereHas('role', fn ($q) => $q->where('name', Role::BRANCH));
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function matchProduct(Collection $products, string $name): ?Product
    {
        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            return null;
        }

        return $products->first(function (Product $product) use ($needle) {
            if (empty($product->item_code)) {
                return false;
            }

            return mb_strtolower((string) $product->display_name) === $needle
                || mb_strtolower((string) $product->name) === $needle
                || mb_strtolower((string) $product->item_code) === $needle;
        });
    }
}
