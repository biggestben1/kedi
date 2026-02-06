<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 15));

        $orders->getCollection()->transform(fn (Order $order) => $this->orderResource($order));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->load('items');

        return response()->json(['data' => $this->orderResource($order)]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_code' => 'required|string|max:50',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:wallet,pay_on_delivery',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_phone' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $itemsByCode = [];
        foreach ($request->items as $row) {
            $code = $row['item_code'] ?? '';
            $qty = (int) ($row['quantity'] ?? 0);
            if ($code !== '' && $qty > 0) {
                $itemsByCode[$code] = ($itemsByCode[$code] ?? 0) + $qty;
            }
        }

        $products = Product::with('category')
            ->whereIn('item_code', array_keys($itemsByCode))
            ->where('is_active', true)
            ->get()
            ->keyBy('item_code');

        $cartItems = [];
        $subtotal = 0.0;
        $totalBv = 0.0;
        $totalPv = 0.0;

        foreach ($itemsByCode as $itemCode => $qty) {
            $product = $products->get($itemCode);
            if (! $product || $qty < 1) {
                continue;
            }
            if ($product->stock < $qty) {
                return response()->json([
                    'message' => "Insufficient stock for {$product->name}. Available: {$product->stock}.",
                ], 422);
            }
            $unitPrice = $product->getPriceForUser($user);
            $lineTotal = $unitPrice * $qty;
            $cartItems[] = (object) [
                'product' => $product,
                'quantity' => (int) $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'line_bv' => $product->bv * $qty,
                'line_pv' => $product->pv * $qty,
            ];
            $subtotal += $lineTotal;
            $totalBv += $product->bv * $qty;
            $totalPv += $product->pv * $qty;
        }

        if (empty($cartItems)) {
            return response()->json(['message' => 'No valid items in cart.'], 422);
        }

        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === Order::PAYMENT_WALLET && ! $user->canPayWithWallet($subtotal)) {
            return response()->json(['message' => 'Insufficient wallet balance.'], 422);
        }

        $order = null;
        DB::transaction(function () use ($user, $cartItems, $subtotal, $totalBv, $totalPv, $paymentMethod, $request, &$order) {
            $order = Order::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateOrderNumber(),
                'subtotal' => $subtotal,
                'total_bv' => $totalBv,
                'total_pv' => $totalPv,
                'payment_method' => $paymentMethod,
                'status' => $paymentMethod === Order::PAYMENT_WALLET ? Order::STATUS_PAID : Order::STATUS_PENDING,
                'shipping_address' => $request->input('shipping_address'),
                'shipping_city' => $request->input('shipping_city'),
                'shipping_state' => $request->input('shipping_state'),
                'shipping_postal_code' => $request->input('shipping_postal_code'),
                'shipping_phone' => $request->input('shipping_phone'),
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $item->product->item_code,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'bv' => $item->product->bv,
                    'pv' => $item->product->pv,
                ]);
            }

            if ($paymentMethod === Order::PAYMENT_WALLET) {
                $user->decrement('wallet_balance', $subtotal);
                $balanceAfter = (float) $user->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $subtotal,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #' . $order->id,
                ]);
            }
        });

        $order->load('items');

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => $this->orderResource($order),
        ], 201);
    }

    private function orderResource(Order $order): array
    {
        return [
            'id' => $order->id,
            'invoice_number' => $order->invoice_number ?? 'ORD-' . $order->id,
            'tracking_number' => $order->tracking_number,
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

    private function generateOrderNumber(): string
    {
        $last = Order::orderByDesc('id')->first();
        $next = $last ? ((int) preg_replace('/[^0-9]/', '', $last->invoice_number ?? (string) $last->id)) + 1 : 1;

        return 'ORD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
