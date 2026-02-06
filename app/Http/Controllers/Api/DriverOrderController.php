<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverOrderController extends Controller
{
    /**
     * List orders available for dispatch (paid, packed, shipped, delivered, completed).
     * Restricted to users with role 'dispatch'.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isDispatch()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = Order::with('items')
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

        $orders = $query->paginate($request->input('per_page', 15));

        $orders->getCollection()->transform(fn (Order $order) => $this->orderResource($order));

        return response()->json($orders);
    }

    /**
     * Show order detail for dispatch.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        if (! $request->user()->isDispatch()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return response()->json(['message' => 'Order not available for dispatch.'], 404);
        }

        $order->load('items', 'user');

        return response()->json([
            'data' => array_merge($this->orderResource($order), [
                'customer_name' => $order->user?->name,
                'customer_email' => $order->user?->email,
                'customer_phone' => $order->user?->phone,
            ]),
        ]);
    }

    /**
     * Update order status: packed, shipped, delivered.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        if (! $request->user()->isDispatch()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return response()->json(['message' => 'Order not available for dispatch.'], 404);
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
            $updates['status'] = Order::STATUS_COMPLETED;
        }

        $order->update($updates);

        $message = match ($status) {
            'packed' => 'Order marked as packed.',
            'shipped' => 'Order marked as shipped.',
            'delivered' => 'Order marked as delivered.',
            default => 'Status updated.',
        };

        return response()->json(['message' => $message, 'data' => $this->orderResource($order->fresh('items'))]);
    }

    /**
     * Update tracking number and optional delivery courier.
     */
    public function updateTracking(Request $request, Order $order): JsonResponse
    {
        if (! $request->user()->isDispatch()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if (! in_array($order->status, Order::dispatchableStatuses(), true)) {
            return response()->json(['message' => 'Order not available for dispatch.'], 404);
        }

        $request->validate([
            'tracking_number' => 'nullable|string|max:100',
            'delivery_courier' => 'nullable|string|max:255',
        ]);

        $order->update([
            'tracking_number' => $request->input('tracking_number') ?: null,
            'delivery_courier' => $request->input('delivery_courier') ?: null,
        ]);

        return response()->json(['message' => 'Tracking info updated.', 'data' => $this->orderResource($order->fresh('items'))]);
    }

    private function orderResource(Order $order): array
    {
        return [
            'id' => $order->id,
            'invoice_number' => $order->invoice_number ?? 'ORD-' . $order->id,
            'tracking_number' => $order->tracking_number,
            'delivery_courier' => $order->delivery_courier,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'subtotal' => (float) $order->subtotal,
            'total_bv' => (float) $order->total_bv,
            'total_pv' => (float) $order->total_pv,
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_state' => $order->shipping_state,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_phone' => $order->shipping_phone,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn ($i) => [
                'item_code' => $i->item_code,
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'line_total' => (float) $i->line_total,
            ])->all(),
        ];
    }
}
