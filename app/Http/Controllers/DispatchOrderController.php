<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DispatchOrderController extends Controller
{
    /**
     * List orders that are paid or in dispatch flow (packed, shipped, delivered).
     */
    public function index(Request $request)
    {
        $query = Order::with('user')
            ->whereIn('status', Order::dispatchableStatuses())
            ->orderByRaw("FIELD(status, 'paid', 'packed', 'shipped', 'delivered', 'completed')")
            ->orderByDesc('created_at');

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

        return view('admin.dispatch.orders.index', [
            'orders' => $orders,
            'statusFilter' => $request->status,
            'search' => $request->search,
        ]);
    }

    /**
     * Show order for packing/shipping: items, customer, status actions.
     */
    public function show(Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404, 'Order not available for dispatch.');
        }

        $order->load('user', 'items');

        // Load product batch/expiry for each item (by item_code)
        $itemCodes = $order->items->pluck('item_code')->unique()->filter()->values()->all();
        $products = Product::whereIn('item_code', $itemCodes)->get()->keyBy('item_code');

        return view('admin.dispatch.orders.show', [
            'order' => $order,
            'productsByItemCode' => $products,
        ]);
    }

    /**
     * Update order status: packed, shipped, delivered.
     */
    public function updateStatus(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
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

        $order->update($updates);

        $message = match ($status) {
            'packed' => 'Order marked as packed.',
            'shipped' => 'Order marked as shipped.',
            'delivered' => 'Order marked as delivered.',
            default => 'Status updated.',
        };

        return back()->with('success', $message);
    }

    /**
     * Update tracking number and optional delivery courier.
     */
    public function updateTracking(Request $request, Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return back()->with('error', 'Order not available for dispatch.');
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
     * Print invoice PDF for order.
     */
    public function invoice(Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }

        $order->load('user', 'items');

        return view('admin.dispatch.orders.print-invoice', ['order' => $order]);
    }

    /**
     * Print delivery note PDF.
     */
    public function deliveryNote(Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }

        $order->load('user', 'items');

        return view('admin.dispatch.orders.print-delivery-note', ['order' => $order]);
    }

    /**
     * Print shipment label (simple layout for printing).
     */
    public function shipmentLabel(Order $order)
    {
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            abort(404);
        }

        $order->load('user');

        return view('admin.dispatch.orders.print-shipment-label', ['order' => $order]);
    }
}
