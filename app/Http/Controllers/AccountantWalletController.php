<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class AccountantWalletController extends Controller
{
    /**
     * Get user IDs that the current user is allowed to see for wallet scope.
     * Returns non-null for: headquarters (self + branch/annex/service_center), branch (self only),
     * accountant created by HQ (HQ scope), or accountant created by branch (that branch + its annex/service_center/accountant).
     */
    private function getHeadquartersScopeUserIds(Request $request): ?array
    {
        $user = $request->user();
        if ($user->role?->name === 'headquarters') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
        }
        if ($user->role?->name === 'branch') {
            // Branch sees only their own wallet account
            return [$user->id];
        }
        if ($user->role?->name === 'service_center') {
            // Service Center sees only their own wallet account
            return [$user->id];
        }
        if ($user->role?->name === 'accountant' && $user->created_by_user_id) {
            $creator = User::with('role')->find($user->created_by_user_id);
            if ($creator && $creator->role?->name === 'headquarters') {
                $hqId = $creator->id;
                return User::where('id', $hqId)
                    ->orWhere(function ($q) use ($hqId) {
                        $q->where('created_by_user_id', $hqId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['service_center', 'annex', 'branch']);
                            });
                    })
                    ->pluck('id')
                    ->all();
            }
            if ($creator && $creator->role?->name === 'branch') {
                // Branch accountant: see only their branch (the creator) + users created by that branch
                $branchId = $creator->id;
                return User::where('id', $branchId)
                    ->orWhere(function ($q) use ($branchId) {
                        $q->where('created_by_user_id', $branchId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['annex', 'service_center', 'accountant']);
                            });
                    })
                    ->pluck('id')
                    ->all();
            }
        }
        return null;
    }

    public function index(Request $request)
    {
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);
        
        $query = WalletTransaction::with('user')->orderByDesc('created_at');
        
        // Filter by allowed users for headquarters
        if ($allowedUserIds !== null) {
            $query->whereIn('user_id', $allowedUserIds);
        }

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

        // Statistics - filtered for headquarters
        $statsQuery = WalletTransaction::query();
        if ($allowedUserIds !== null) {
            $statsQuery->whereIn('user_id', $allowedUserIds);
        }
        
        $totalTransactions = (clone $statsQuery)->count();
        $totalPending = (clone $statsQuery)->where('status', WalletTransaction::STATUS_PENDING)->count();
        $totalApproved = (clone $statsQuery)->where('status', WalletTransaction::STATUS_ACCEPTED)->count();
        $totalRejected = (clone $statsQuery)->where('status', WalletTransaction::STATUS_REJECTED)->count();
        $totalCredits = (clone $statsQuery)->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED)
            ->sum('amount');
        $totalDebits = (clone $statsQuery)->where('type', WalletTransaction::TYPE_DEBIT)
            ->sum('amount');

        $ownAccountOnly = in_array($request->user()?->role?->name, ['branch', 'service_center'], true);

        return view('admin.accountant.wallet.index', [
            'transactions' => $transactions,
            'statusFilter' => $request->status,
            'typeFilter' => $request->type,
            'search' => $request->search,
            'dateFrom' => $request->date_from,
            'dateTo' => $request->date_to,
            'ownAccountOnly' => $ownAccountOnly,
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
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        $query = User::with('role')->whereNotNull('wallet_balance')
            ->where('wallet_balance', '>', 0)
            ->orderByDesc('wallet_balance');
        
        // Filter by allowed users for headquarters / headquarters accountant
        if ($allowedUserIds !== null) {
            $query->whereIn('id', $allowedUserIds);
        }

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

        // Statistics - filtered for headquarters
        $statsQuery = User::whereNotNull('wallet_balance')->where('wallet_balance', '>', 0);
        if ($allowedUserIds !== null) {
            $statsQuery->whereIn('id', $allowedUserIds);
        }
        
        $totalUsers = (clone $statsQuery)->count();
        $totalBalance = (clone $statsQuery)->sum('wallet_balance');

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
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        if ($allowedUserIds !== null && !in_array($user->id, $allowedUserIds)) {
            abort(403, 'You can only view wallet transactions for your headquarters scope (Headquarters account and its Service Center, Annex, Branch users).');
        }
        
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.accountant.wallet.user-transactions', [
            'user' => $user,
            'transactions' => $transactions,
        ]);
    }
}
