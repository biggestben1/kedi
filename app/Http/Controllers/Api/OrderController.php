<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\ServiceCenterStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
            'kd_id' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $branchUserId = ($user->role?->name === 'annex' && $user->created_by_user_id) ? (int) $user->created_by_user_id : null;

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
            $availableStock = $branchUserId
                ? $this->getStockForUser($branchUserId, $user->role?->name, $product->id)
                : (int) $product->stock;
            if ($availableStock < $qty) {
                return response()->json([
                    'message' => "Insufficient stock for {$product->name}. Available: {$availableStock}.",
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

        $coupon = null;
        $discountAmount = 0;
        if ($request->filled('coupon_code')) {
            $coupon = \App\Models\Coupon::where('code', strtoupper($request->coupon_code))->first();
            if ($coupon && $coupon->isValid()) {
                $discountAmount = ($coupon->discount_percentage / 100) * $subtotal;
            } else {
                return response()->json(['message' => 'Invalid or expired coupon code.'], 422);
            }
        }

        $totalAmount = max(0, $subtotal - $discountAmount);

        if (empty($cartItems)) {
            return response()->json(['message' => 'No valid items in cart.'], 422);
        }

        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === Order::PAYMENT_WALLET && ! $user->canPayWithWallet($totalAmount)) {
            return response()->json(['message' => 'Insufficient wallet balance.'], 422);
        }

        $order = null;
        $branchUserIdForOrder = $branchUserId ?? null;
        DB::transaction(function () use ($user, $cartItems, $subtotal, $totalBv, $totalPv, $paymentMethod, $request, $branchUserIdForOrder, &$order) {
            $order = Order::create([
                'user_id' => $user->id,
                'branch_user_id' => $branchUserIdForOrder,
                'invoice_number' => Order::generateOrderNumber(),
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
                'kd_id' => $request->input('kd_id'),
                'customer_name' => $request->input('customer_name'),
                'coupon_id' => $coupon ? $coupon->id : null,
                'coupon_code' => $coupon ? $coupon->code : null,
                'discount_amount' => $discountAmount,
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
                // Stock will be deducted when order is marked as completed
            }

            if ($paymentMethod === Order::PAYMENT_WALLET) {
                $user->decrement('wallet_balance', $totalAmount);
                $balanceAfter = (float) $user->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $totalAmount,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #' . $order->id,
                ]);
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }
        });

        $order->load(['user', 'items']);

        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: ' . $e->getMessage());
        }

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
            'discount_amount' => (float) ($order->discount_amount ?? 0),
            'shipping_cost' => (float) ($order->shipping_cost ?? 0),
            'total' => (float) ($order->subtotal - ($order->discount_amount ?? 0) + ($order->shipping_cost ?? 0)),
            'total_bv' => (float) $order->total_bv,
            'total_pv' => (float) $order->total_pv,
            'shipping_address' => $order->shipping_address,
            'shipping_city' => $order->shipping_city,
            'shipping_state' => $order->shipping_state,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_phone' => $order->shipping_phone,
            'kd_id' => $order->kd_id,
            'customer_name' => $order->customer_name,
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

    private function getStockForUser(int $userId, ?string $role, int $productId): int
    {
        if ($role === 'branch') {
            return BranchStock::getQuantity($userId, $productId);
        }
        if ($role === 'service_center') {
            return ServiceCenterStock::getQuantity($userId, $productId);
        }
        if ($role === 'annex') {
            return AnnexStock::getQuantity($userId, $productId);
        }
        return BranchStock::getQuantity($userId, $productId);
    }
}
