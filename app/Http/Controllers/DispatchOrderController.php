<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusUpdateMail;
use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\ServiceCenterStock;
use App\Models\HeadquartersStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DispatchOrderController extends Controller
{
    /**
     * List orders that are paid or in dispatch flow (packed, shipped, delivered).
     */
    public function index(Request $request)
    {
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        $query = Order::with('user')
            ->whereIn('status', Order::dispatchableStatuses())
            ->orderByRaw("FIELD(status, 'paid', 'packed', 'shipped', 'delivered', 'completed')")
            ->orderByDesc('created_at');
        
        // Filter for headquarters users - show orders from their own account + their Service Center, Annex, Branch users
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qb) use ($q) {
                $qb->where('invoice_number', 'like', "%{$q}%")
                    ->orWhere('tracking_number', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $ordersByCustomer = $orders->getCollection()->groupBy('user_id');

        return view('admin.dispatch.orders.index', [
            'orders' => $orders,
            'ordersByCustomer' => $ordersByCustomer,
            'statusFilter' => $request->status,
            'search' => $request->search,
        ]);
    }

    /**
     * Show order for packing/shipping: items, customer, status actions.
     */
    public function show(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404, 'Order not available for dispatch.');
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can view this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only view orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $order->load('user', 'items');

        // Load product batch/expiry for each item (by item_code)
        $itemCodes = $order->items->pluck('item_code')->unique()->filter()->values()->all();
        $products = Product::whereIn('item_code', $itemCodes)->get()->keyBy('item_code');

        // Fetch all active products for possible exchange (to allow combinations)
        $allProducts = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.dispatch.orders.show', [
            'order' => $order,
            'productsByItemCode' => $products,
            'allProducts' => $allProducts,
        ]);
    }

    /**
     * Invoice-style read-only view of an order (View button from index).
     */
    public function viewOrder(Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404, 'Order not available.');
        }

        $order->load('user', 'items');

        return view('admin.dispatch.orders.view', compact('order'));
    }

    /**
     * Update order status: packed, shipped, delivered.
     */
    public function updateStatus(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can update this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only update orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $request->validate([
            'status' => 'required|in:packed,shipped,delivered',
        ]);

        $status = $request->status;
        $updates = ['status' => $status];

        if ($status === Order::STATUS_PACKED) {
            $updates['packed_at'] = now();
        }
        if ($status === Order::STATUS_SHIPPED) {
            $updates['shipped_at'] = now();
        }
        if ($status === Order::STATUS_DELIVERED) {
            $updates['delivered_at'] = now();
            $updates['status'] = Order::STATUS_COMPLETED; // treat delivered as completed
        }

        // Check if order was already completed before update
        $wasAlreadyCompleted = $order->status === Order::STATUS_COMPLETED;
        
        $order->update($updates);

        // Deduct stock when order is marked as completed (only if it wasn't already completed)
        if ($updates['status'] === Order::STATUS_COMPLETED && !$wasAlreadyCompleted) {
            $this->deductStockFromOrder($order);
        }

        $statusLabel = match ($status) {
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            default => ucfirst($status),
        };
        $message = match ($status) {
            'packed' => 'Order marked as packed.',
            'shipped' => 'Order marked as shipped.',
            'delivered' => 'Order marked as delivered.',
            default => 'Status updated.',
        };

        $order->load(['user', 'items']);
        if ($order->user?->email) {
            try {
                Mail::to($order->user->email)->send(new OrderStatusUpdateMail($order, $statusLabel));
            } catch (\Throwable $e) {
                \Log::warning('Order status update email failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', $message);
    }

    public function exchangeItem(Request $request, Order $order, \App\Models\OrderItem $item)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
        }

        if ($item->order_id !== $order->id) {
            abort(404);
        }

        $request->validate([
            'replacement_items' => 'required|array|min:1',
            'replacement_items.*.product_id' => 'required|exists:products,id',
            'replacement_items.*.quantity' => 'required|integer|min:1',
        ]);

        $originalTotal = (float) $item->line_total; // unit_price * quantity
        $newTotal = 0;
        $totalBv = 0;
        $totalPv = 0;

        $replacements = [];
        foreach ($request->replacement_items as $rItem) {
            $product = Product::findOrFail($rItem['product_id']);
            $qty = (int) $rItem['quantity'];
            $price = (float) $product->getPriceForUser($order->user);
            
            $lineTotal = $price * $qty;
            $newTotal += $lineTotal;
            $totalBv += (float) $product->bv * $qty;
            $totalPv += (float) $product->pv * $qty;

            $replacements[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'bv' => (float) $product->bv * $qty,
                'pv' => (float) $product->pv * $qty,
            ];
        }

        if ($newTotal > $originalTotal + 0.01) {
            return back()->with('error', 'Total replacement price (₦' . number_format($newTotal, 2) . ') exceeds original item total (₦' . number_format($originalTotal, 2) . ').');
        }

        DB::transaction(function () use ($order, $item, $replacements, $newTotal, $originalTotal, $totalBv, $totalPv) {
            $priceDifference = $originalTotal - $newTotal;
            $bvDifference = (float) $item->bv - $totalBv;
            $pvDifference = (float) $item->pv - $totalPv;

            // Create new items
            foreach ($replacements as $r) {
                $order->items()->create([
                    'item_code' => $r['product']->item_code,
                    'product_name' => $r['product']->name,
                    'quantity' => $r['quantity'],
                    'unit_price' => $r['unit_price'],
                    'line_total' => $r['line_total'],
                    'bv' => $r['bv'],
                    'pv' => $r['pv'],
                ]);
            }

            // Update order totals
            $order->subtotal -= $priceDifference;
            $order->total_bv -= $bvDifference;
            $order->total_pv -= $pvDifference;
            $order->save();

            // Delete original item
            $item->delete();
        });

        return back()->with('success', 'Item exchanged successfully.');
    }

    /**
     * Update tracking number and optional delivery courier.
     */
    public function updateTracking(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can update this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only update orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'delivery_courier' => 'nullable|string|max:255',
        ]);

        $order->update([
            'tracking_number' => $request->input('tracking_number') ?: null,
            'delivery_courier' => $request->input('delivery_courier') ?: null,
        ]);

        return back()->with('success', 'Tracking info updated.');
    }

    /**
     * Update order shipping cost.
     */
    public function updateShippingCost(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can update this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only update orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $request->validate([
            'shipping_cost' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $order->update([
            'shipping_cost' => $request->input('shipping_cost') ? (float) $request->input('shipping_cost') : 0,
        ]);

        return back()->with('success', 'Shipping cost updated successfully.');
    }

    /**
     * Print invoice PDF for order.
     */
    public function invoice(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can view this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only view orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $order->load('user', 'items');

        return view('admin.dispatch.orders.print-invoice', ['order' => $order]);
    }

    /**
     * Print delivery note PDF.
     */
    public function deliveryNote(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can view this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only view orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $order->load('user', 'items');

        return view('admin.dispatch.orders.print-delivery-note', ['order' => $order]);
    }

    /**
     * Print shipment label (simple layout for printing).
     */
    public function shipmentLabel(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }
        
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        
        // Check if headquarters user can view this order
        if ($headquartersOnly) {
            $headquartersUserId = $request->user()->id;
            $allowedUserIds = User::where('id', $headquartersUserId)
                ->orWhere(function ($q) use ($headquartersUserId) {
                    $q->where('created_by_user_id', $headquartersUserId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
            
            if (!in_array($order->user_id, $allowedUserIds)) {
                abort(403, 'You can only view orders for your own account and your Service Center, Annex, and Branch users.');
            }
        }

        $order->load('user');

        return view('admin.dispatch.orders.print-shipment-label', ['order' => $order]);
    }

    /**
     * Deduct stock from products when order is completed.
     */
    private function deductStockFromOrder(Order $order): void
    {
        $order->load('items', 'user.role');
        $branchUserId = $order->branch_user_id;
        $orderUser = $order->user;
        $isHeadquarters = $orderUser && $orderUser->role?->name === 'headquarters';

        DB::transaction(function () use ($order, $branchUserId, $isHeadquarters, $orderUser) {
            foreach ($order->items as $item) {
                // Skip invoice items (they don't map to products)
                if (str_starts_with($item->item_code, 'INV-')) {
                    continue;
                }

                $product = Product::where('item_code', $item->item_code)->first();
                if (!$product) {
                    continue;
                }

                if ($isHeadquarters) {
                    // For Headquarters users, stock was already deducted when order was paid
                    // Skip deduction here to avoid double-deduction
                    continue;
                } elseif ($branchUserId) {
                    $stockUser = \App\Models\User::with('role')->find($branchUserId);
                    $role = $stockUser?->role?->name ?? '';
                    $product->decrement('stock', $item->quantity);
                    if ($role === 'service_center') {
                        ServiceCenterStock::decrementStock($branchUserId, $product->id, $item->quantity);
                    } elseif ($role === 'annex') {
                        AnnexStock::decrementStock($branchUserId, $product->id, $item->quantity);
                    } else {
                        BranchStock::decrementStock($branchUserId, $product->id, $item->quantity);
                    }
                } else {
                    // For regular users, deduct from main product stock
                    $product->decrement('stock', $item->quantity);
                }
            }
        });
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('success', 'Order moved to trash.');
    }

    public function trashed(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $users = User::all()->keyBy('id'); // for index lookups if needed

        $query = Order::onlyTrashed()->with('user')->orderByDesc('deleted_at');
        $orders = $query->paginate(20)->withQueryString();

        return view('admin.orders.trashed', compact('orders'));
    }

    public function restore($id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $order = Order::withTrashed()->findOrFail($id);
        $order->restore();

        return redirect()->route('admin.orders.trashed')->with('success', "Order #{$order->id} has been restored.");
    }

    public function forceDelete($id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $order = Order::withTrashed()->findOrFail($id);
        $order->forceDelete();

        return redirect()->route('admin.orders.trashed')->with('success', "Order #{$order->id} has been permanently deleted.");
    }
}
