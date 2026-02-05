<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the user dashboard (requires auth).
     */
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $cartItems = [];
        $cartSubtotal = 0;
        $cartBv = 0;
        $cartPv = 0;

        foreach ($cart as $itemCode => $qty) {
            $product = Product::where('item_code', $itemCode)->where('is_active', true)->first();
            if ($product && $qty > 0) {
                $cartItems[] = (object) [
                    'product' => $product,
                    'quantity' => (int) $qty,
                    'line_total' => $product->price * $qty,
                    'line_bv' => $product->bv * $qty,
                    'line_pv' => $product->pv * $qty,
                ];
                $cartSubtotal += $product->price * $qty;
                $cartBv += $product->bv * $qty;
                $cartPv += $product->pv * $qty;
            }
        }

        $cartCount = array_sum($cart);

        return view('dashboard', compact('cartItems', 'cartSubtotal', 'cartBv', 'cartPv', 'cartCount'));
    }
}
