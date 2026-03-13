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
        
        // Only show transactions for the logged-in user
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Filter banks: HQ sees their own; Branch/Service Center see their HQ's banks; Annex/Dispatch/Accountant under SC see SC's HQ banks
        $banksQuery = Bank::where('is_active', true);
        $hqId = null;
        if ($user->role?->name === 'headquarters') {
            $hqId = (int) $user->id;
        } elseif ($user->role?->name === 'branch') {
            $hqId = (int) $user->created_by_user_id;
        } elseif ($user->role?->name === 'service_center' && $user->created_by_user_id) {
            $branch = \App\Models\User::find($user->created_by_user_id);
            $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
        } elseif (in_array($user->role?->name, ['annex', 'dispatch', 'accountant'], true) && $user->created_by_user_id) {
            $creator = \App\Models\User::with('role')->find($user->created_by_user_id);
            if ($creator && $creator->role?->name === 'service_center' && $creator->created_by_user_id) {
                $branch = \App\Models\User::find($creator->created_by_user_id);
                $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
            } elseif ($creator && $creator->role?->name === 'branch') {
                $hqId = (int) $creator->created_by_user_id;
            }
        }
        if ($hqId) {
            $banksQuery->where('headquarters_user_id', $hqId);
        }
        $banks = $banksQuery->orderBy('name')->get();

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
            'amount' => 'required|numeric|min:1|max:1000000000000',
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
