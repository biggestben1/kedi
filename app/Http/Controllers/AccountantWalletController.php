<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AccountantWalletController extends Controller
{
    public function index(Request $request)
    {
        $query = WalletTransaction::with('user')->orderByDesc('created_at');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by user name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(50)->withQueryString();

        // Statistics
        $totalTransactions = WalletTransaction::count();
        $totalPending = WalletTransaction::where('status', WalletTransaction::STATUS_PENDING)->count();
        $totalApproved = WalletTransaction::where('status', WalletTransaction::STATUS_ACCEPTED)->count();
        $totalRejected = WalletTransaction::where('status', WalletTransaction::STATUS_REJECTED)->count();
        $totalCredits = WalletTransaction::where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED)
            ->sum('amount');
        $totalDebits = WalletTransaction::where('type', WalletTransaction::TYPE_DEBIT)
            ->sum('amount');

        return view('admin.accountant.wallet.index', [
            'transactions' => $transactions,
            'statusFilter' => $request->status,
            'typeFilter' => $request->type,
            'search' => $request->search,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'stats' => [
                'total_transactions' => $totalTransactions,
                'total_pending' => $totalPending,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'total_credits' => $totalCredits,
                'total_debits' => $totalDebits,
            ],
        ]);
    }

    public function users(Request $request)
    {
        $query = User::with('role')->whereNotNull('wallet_balance')
            ->where('wallet_balance', '>', 0)
            ->orderByDesc('wallet_balance');

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate(50)->withQueryString();

        // Statistics
        $totalUsers = User::whereNotNull('wallet_balance')->where('wallet_balance', '>', 0)->count();
        $totalBalance = User::sum('wallet_balance');

        return view('admin.accountant.wallet.users', [
            'users' => $users,
            'search' => $request->search,
            'roleFilter' => $request->role,
            'stats' => [
                'total_users' => $totalUsers,
                'total_balance' => $totalBalance,
            ],
        ]);
    }

    public function userTransactions(Request $request, User $user)
    {
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.accountant.wallet.user-transactions', [
            'user' => $user,
            'transactions' => $transactions,
        ]);
    }
}
