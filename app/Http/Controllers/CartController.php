<?php

namespace App\Http\Controllers;

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
}
