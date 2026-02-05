<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminWalletTopupController extends Controller
{
    public function index(Request $request)
    {
        $pending = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.wallet-topups', [
            'pending' => $pending,
        ]);
    }

    public function approved(Request $request)
    {
        $transactions = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED)
            ->orderByDesc('approved_at')
            ->get();

        return view('admin.wallet-topups-approved', [
            'transactions' => $transactions,
        ]);
    }

    public function rejected(Request $request)
    {
        $transactions = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_REJECTED)
            ->orderByDesc('approved_at')
            ->get();

        return view('admin.wallet-topups-rejected', [
            'transactions' => $transactions,
        ]);
    }

    public function approve(Request $request, WalletTransaction $tx)
    {
        if ($tx->status !== WalletTransaction::STATUS_PENDING) {
            return back()->with('error', 'This top-up is not pending.');
        }

        DB::transaction(function () use ($tx) {
            $user = $tx->user()->lockForUpdate()->first();

            $user->increment('wallet_balance', (float) $tx->amount);
            $balanceAfter = (float) $user->fresh()->wallet_balance;

            $tx->update([
                'status' => WalletTransaction::STATUS_ACCEPTED,
                'approved_at' => now(),
                'balance_after' => $balanceAfter,
                'reference' => $tx->reference ?: ('Top-up accepted'),
            ]);
        });

        return back()->with('success', 'Top-up accepted and wallet credited.');
    }

    public function reject(Request $request, WalletTransaction $tx)
    {
        if ($tx->status !== WalletTransaction::STATUS_PENDING) {
            return back()->with('error', 'This top-up is not pending.');
        }

        $tx->update([
            'status' => WalletTransaction::STATUS_REJECTED,
            'approved_at' => now(),
            'balance_after' => null,
        ]);

        return back()->with('success', 'Top-up rejected.');
    }
}

