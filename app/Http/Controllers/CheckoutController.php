<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    private function getCartData(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $cartItems = [];
        $cartSubtotal = 0;
        $cartBv = 0;
        $cartPv = 0;
        $user = $request->user();

        foreach ($cart as $itemCode => $qty) {
            $product = Product::with('category')->where('item_code', $itemCode)->where('is_active', true)->first();
            if ($product && $qty > 0) {
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
                $cartSubtotal += $lineTotal;
                $cartBv += $product->bv * $qty;
                $cartPv += $product->pv * $qty;
            }
        }

        $cartCount = array_sum($cart);
        return compact('cartItems', 'cartSubtotal', 'cartBv', 'cartPv', 'cartCount');
    }

    public function show(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('message', 'Your cart is empty. Add items to checkout.');
        }

        $user = $request->user();
        $walletBalance = (float) ($user->wallet_balance ?? 0);
        $canPayWithWallet = $user->canPayWithWallet($data['cartSubtotal']);

        return view('checkout.show', array_merge($data, [
            'walletBalance' => $walletBalance,
            'canPayWithWallet' => $canPayWithWallet,
        ]));
    }

    public function placeOrder(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $request->validate([
            'payment_method' => 'required|in:wallet,pay_on_delivery',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_phone' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === 'wallet' && ! $user->canPayWithWallet($data['cartSubtotal'])) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        DB::transaction(function () use ($user, $data, $paymentMethod, $request) {
            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $data['cartSubtotal'],
                'total_bv' => $data['cartBv'],
                'total_pv' => $data['cartPv'],
                'payment_method' => $paymentMethod,
                'status' => $paymentMethod === Order::PAYMENT_WALLET ? Order::STATUS_PAID : Order::STATUS_PENDING,
                'shipping_address' => $request->input('shipping_address'),
                'shipping_city' => $request->input('shipping_city'),
                'shipping_state' => $request->input('shipping_state'),
                'shipping_postal_code' => $request->input('shipping_postal_code'),
                'shipping_phone' => $request->input('shipping_phone'),
            ]);

            foreach ($data['cartItems'] as $item) {
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
                $user->decrement('wallet_balance', $data['cartSubtotal']);
                $balanceAfter = (float) $user->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $data['cartSubtotal'],
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #' . $order->id,
                ]);
            }
        });

        $request->session()->forget('cart');

        return redirect()->route('dashboard')->with('success', 'Order placed successfully. Thank you!');
    }
}
