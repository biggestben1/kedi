<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        $cartCount = array_sum($request->session()->get('cart', []));

        return view('orders.index', ['orders' => $orders, 'cartCount' => $cartCount]);
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }

        $order->load('items');
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('orders.show', ['order' => $order, 'cartCount' => $cartCount]);
    }
}
