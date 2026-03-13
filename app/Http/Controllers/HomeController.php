<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DpbvCollection;
use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        $search = $request->input('q', '');
        if (trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('item_code', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('pack_size', 'like', $term);
            });
        }

        $categoryId = $request->query('category_id');
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('sort_order')->orderBy('item_code')->get();
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

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

        $showKdModal = false;
        $totalDpbv = 0;
        $dpbvNairaEquivalent = 0;
        $canBuyWithDpbv = false;
        $hasDpbvBalance = false;

        if ($request->user()) {
            $kdId = trim((string) $request->session()->get('kd_id', ''));
            $customerName = trim((string) $request->session()->get('customer_name', ''));
            $showKdModal = $kdId === '' || $customerName === '';
            
            // Calculate DPBV balance
            $totalDpbv = (float) DpbvCollection::where('user_id', $user->id)->sum('dpbv');
            $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
            // Check if all products in cart allow DPBV
            $allProductsAllowDpbv = true;
            foreach ($cartItems as $item) {
                if (!($item->product->can_use_dpbv ?? true)) {
                    $allProductsAllowDpbv = false;
                    break;
                }
            }
            $canBuyWithDpbv = $dpbvNairaEquivalent >= $cartSubtotal && $cartCount > 0 && $allProductsAllowDpbv;
            $hasDpbvBalance = $dpbvNairaEquivalent > 0; // Show button if user has any DPBV balance
        }

        return view('home', compact('products', 'cartItems', 'cartSubtotal', 'cartBv', 'cartPv', 'cartCount', 'search', 'categories', 'categoryId', 'showKdModal', 'totalDpbv', 'dpbvNairaEquivalent', 'canBuyWithDpbv', 'hasDpbvBalance'));
    }
}
