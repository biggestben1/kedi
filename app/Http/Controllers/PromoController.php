<?php

namespace App\Http\Controllers;

use App\Models\PromoCollection;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $promos = PromoCollection::where('user_id', auth()->id())
            ->orderByDesc('id')
            ->paginate(30);

        $cartCount = array_sum($request->session()->get('cart', []));

        return view('promo.index', [
            'promos' => $promos,
            'cartCount' => $cartCount,
        ]);
    }
}
