<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $items = [];
        $subtotal = 0;
        $totalBv = 0;
        $totalPv = 0;

        $user = $request->user();
        foreach ($cart as $itemCode => $qty) {
            $product = Product::with('category')->where('item_code', $itemCode)->where('is_active', true)->first();
            if ($product && $qty > 0) {
                $product->image_url = $product->image_url;
                $unitPrice = $product->getPriceForUser($user);
                $lineTotal = $unitPrice * $qty;
                $items[] = (object) [
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
        }

        return response()->json([
            'items' => $items,
            'subtotal' => $subtotal,
            'total_bv' => round($totalBv, 2),
            'total_pv' => round($totalPv, 2),
            'count' => array_sum($cart),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'item_code' => 'required|string|max:20',
            'quantity' => 'nullable|integer|min:1',
        ]);

        // Guests can add to cart without KD NO/Customer Name; they can provide it later at checkout

        $itemCode = $request->input('item_code');
        $quantity = (int) $request->input('quantity', 1);

        $product = Product::where('item_code', $itemCode)->where('is_active', true)->first();
        if (! $product) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Product not found.'], 404);
            }
            return back()->with('error', 'Product not found.');
        }

        $cart = $request->session()->get('cart', []);
        $cart[$itemCode] = ($cart[$itemCode] ?? 0) + $quantity;
        $request->session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart_count' => array_sum($cart),
                'message' => "{$product->display_name} added to cart.",
            ]);
        }

        return back()->with('success', "{$product->display_name} added to cart.");
    }

    public function update(Request $request)
    {
        $request->validate([
            'item_code' => 'required|string|max:20',
            'quantity' => 'required|integer|min:0',
        ]);

        $itemCode = $request->input('item_code');
        $quantity = (int) $request->input('quantity');

        $cart = $request->session()->get('cart', []);

        if ($quantity <= 0) {
            unset($cart[$itemCode]);
        } else {
            $product = Product::where('item_code', $itemCode)->where('is_active', true)->first();
            if (! $product) {
                unset($cart[$itemCode]);
            } else {
                $cart[$itemCode] = $quantity;
            }
        }

        $request->session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart_count' => array_sum($cart),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Request $request, string $item_code)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$item_code]);
        $request->session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cart_count' => array_sum($cart),
            ]);
        }

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('cart');

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'cart_count' => 0]);
        }

        return back()->with('success', 'Cart cleared.');
    }

    /**
     * Add all items from selected orders to cart (Add for supply).
     */
    public function addFromSelectedOrders(Request $request)
    {
        $ids = $request->input('order_ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $ids))));
        } elseif (is_array($ids)) {
            $ids = array_filter(array_map('intval', $ids));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->route('orders.index')->with('error', 'Select at least one order to add for supply.');
        }

        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('orders.index')->with('error', 'No valid orders selected.');
        }

        $grouped = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (empty($item->item_code)) {
                    continue;
                }
                $product = Product::where('item_code', $item->item_code)->where('is_active', true)->first();
                if (! $product) {
                    continue;
                }
                $qty = (int) $item->quantity;
                if ($qty < 1) {
                    continue;
                }
                $code = $item->item_code;
                $unitPrice = $product->getPriceForUser($request->user());
                if (! isset($grouped[$code])) {
                    $grouped[$code] = [
                        'item_code' => $code,
                        'product_name' => $item->product_name,
                        'quantity' => 0,
                        'unit_price' => $unitPrice,
                        'line_total' => 0,
                    ];
                }
                $grouped[$code]['quantity'] += $qty;
                $grouped[$code]['line_total'] += $unitPrice * $qty;
            }
        }

        if (empty($grouped)) {
            return redirect()->route('orders.index')->with('error', 'No valid products from selected orders.');
        }

        $addedItems = array_values($grouped);
        $totalQty = array_sum(array_column($addedItems, 'quantity'));

        $request->session()->put('added_items', $addedItems);

        return redirect()->route('orders.index')
            ->with('success', $totalQty . ' item(s) added for supply.');
    }

    /**
     * Apply a coupon code to the session.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $coupon = \App\Models\Coupon::where('code', $code)->first();

        if (!$coupon) {
            return back()->with('error', 'Invalid coupon code.');
        }

        if (!$coupon->isValid()) {
            return back()->with('error', 'This coupon is inactive or has expired.');
        }

        $request->session()->put('coupon_code', $coupon->code);

        return back()->with('success', 'Coupon applied! Your discount will be calculated at checkout.');
    }

    /**
     * Remove the coupon code from the session.
     */
    public function removeCoupon(Request $request)
    {
        $request->session()->forget('coupon_code');
        return back()->with('success', 'Coupon removed.');
    }
}
