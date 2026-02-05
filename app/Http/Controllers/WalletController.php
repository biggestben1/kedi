<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        $cart = $request->session()->get('cart', []);
        $cartCount = array_sum($cart);

        return view('wallet.index', [
            'walletBalance' => (float) ($user->wallet_balance ?? 0),
            'transactions' => $transactions,
            'banks' => $banks,
            'cartCount' => $cartCount,
        ]);
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:99999999',
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();
        $amount = (float) $request->input('amount');

        $path = $request->file('proof')->store('wallet_proofs', 'public');

        DB::transaction(function () use ($user, $amount, $path) {
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => null,
                'reference' => 'Top-up (proof submitted)',
                'status' => WalletTransaction::STATUS_PENDING,
                'proof_path' => $path,
                'approved_at' => null,
            ]);
        });

        return redirect()->route('wallet.index')
            ->with('success', 'Top-up proof submitted: ₦' . number_format($amount, 2) . '. Your wallet will be credited after admin approval.');
    }
}
