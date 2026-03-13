<?php

namespace App\Http\Controllers;

use App\Models\DpbvCollection;
use App\Models\Order;
use Illuminate\Http\Request;

class DpbvController extends Controller
{
    /**
     * Show the current user's DPBV records.
     */
    public function index(Request $request)
    {
        $collections = DpbvCollection::where('user_id', auth()->id())
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(30);

        $totalDpbv = DpbvCollection::where('user_id', auth()->id())->sum('dpbv');
        // Calculate naira equivalent: (Total DPBV * 0.95) * 990
        $nairaEquivalent = ($totalDpbv * 0.95) * 990;
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('dpbv.index', [
            'collections' => $collections,
            'totalDpbv' => $totalDpbv,
            'nairaEquivalent' => $nairaEquivalent,
            'cartCount' => $cartCount,
        ]);
    }

    /**
     * Show DPBV spending history (negative DPBV entries and orders).
     */
    public function spending(Request $request)
    {
        $user = $request->user();
        
        // Get negative DPBV entries (spending)
        $spending = DpbvCollection::where('user_id', $user->id)
            ->where('dpbv', '<', 0)
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(30);

        // Get orders paid with DPBV
        $dpbvOrders = Order::where('user_id', $user->id)
            ->where(function($query) {
                $query->where('payment_method', Order::PAYMENT_DPBV)
                    ->orWhere('is_dpbv_order', true);
            })
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        $totalSpent = abs((float) DpbvCollection::where('user_id', $user->id)->where('dpbv', '<', 0)->sum('dpbv'));
        $totalSpentNaira = ($totalSpent * 0.95) * 990;
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('dpbv.spending', [
            'spending' => $spending,
            'dpbvOrders' => $dpbvOrders,
            'totalSpent' => $totalSpent,
            'totalSpentNaira' => $totalSpentNaira,
            'cartCount' => $cartCount,
        ]);
    }
}
