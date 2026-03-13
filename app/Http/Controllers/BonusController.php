<?php

namespace App\Http\Controllers;

use App\Models\BonusCollection;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index(Request $request)
    {
        $bonuses = BonusCollection::where('user_id', auth()->id())
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(30);

        $totalBonus = BonusCollection::where('user_id', auth()->id())->sum('total');
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('bonus.index', [
            'bonuses' => $bonuses,
            'totalBonus' => $totalBonus,
            'cartCount' => $cartCount,
        ]);
    }
}
