<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $balance = (float) ($user->wallet_balance ?? 0);

        return response()->json([
            'balance' => $balance,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = WalletTransaction::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'balance_after' => $tx->balance_after !== null ? (float) $tx->balance_after : null,
                'reference' => $tx->reference,
                'status' => $tx->status,
                'created_at' => $tx->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $transactions]);
    }

    public function topUp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:1000000000000',
            'reference' => 'nullable|string|max:255',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $user = $request->user();
        $amount = (float) $request->input('amount');
        $proofPath = null;

        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('wallet_proofs', 'public');
        }

        $tx = null;
        DB::transaction(function () use ($user, $amount, $proofPath, $request, &$tx) {
            $tx = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'balance_after' => null,
                'reference' => $request->input('reference', 'Top-up (proof submitted)'),
                'status' => WalletTransaction::STATUS_PENDING,
                'proof_path' => $proofPath,
                'approved_at' => null,
            ]);
        });

        return response()->json([
            'message' => 'Top-up request submitted. Your wallet will be credited after approval.',
            'data' => [
                'id' => $tx->id,
                'amount' => (float) $tx->amount,
                'status' => $tx->status,
                'created_at' => $tx->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
