<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminWalletTopupController extends Controller
{
    /**
     * Get user IDs that the current user is allowed to see (headquarters, branch, or service_center scope).
     * Returns non-null for: headquarters, branch, service_center, or accountant created by HQ/branch/service_center.
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
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'accountant']);
                        });
                })
                ->pluck('id')
                ->all();
        }
        if ($user->role?->name === 'service_center') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['annex', 'dispatch', 'accountant']);
                        });
                })
                ->pluck('id')
                ->all();
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
                $branchId = $creator->id;
                return User::where('id', $branchId)
                    ->orWhere(function ($q) use ($branchId) {
                        $q->where('created_by_user_id', $branchId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['service_center', 'annex', 'accountant']);
                            });
                    })
                    ->pluck('id')
                    ->all();
            }
            if ($creator && $creator->role?->name === 'service_center') {
                $scId = $creator->id;
                return User::where('id', $scId)
                    ->orWhere(function ($q) use ($scId) {
                        $q->where('created_by_user_id', $scId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['annex', 'dispatch', 'accountant']);
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

        $query = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_PENDING);

        if ($allowedUserIds !== null) {
            $query->whereIn('user_id', $allowedUserIds);
        }

        $pending = $query->orderByDesc('created_at')->get();

        return view('admin.wallet-topups', [
            'pending' => $pending,
        ]);
    }

    public function approved(Request $request)
    {
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        $query = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED);

        if ($allowedUserIds !== null) {
            $query->whereIn('user_id', $allowedUserIds);
        }

        $transactions = $query->orderByDesc('approved_at')->get();

        return view('admin.wallet-topups-approved', [
            'transactions' => $transactions,
        ]);
    }

    public function rejected(Request $request)
    {
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        $query = WalletTransaction::with('user')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_REJECTED);

        if ($allowedUserIds !== null) {
            $query->whereIn('user_id', $allowedUserIds);
        }

        $transactions = $query->orderByDesc('approved_at')->get();

        return view('admin.wallet-topups-rejected', [
            'transactions' => $transactions,
        ]);
    }

    public function approve(Request $request, WalletTransaction $tx)
    {
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        if ($allowedUserIds !== null && !in_array($tx->user_id, $allowedUserIds)) {
            abort(403, 'You can only approve wallet top-ups for your headquarters scope (Headquarters account and its Service Center, Annex, Branch users).');
        }

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
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        if ($allowedUserIds !== null && !in_array($tx->user_id, $allowedUserIds)) {
            abort(403, 'You can only reject wallet top-ups for your headquarters scope (Headquarters account and its Service Center, Annex, Branch users).');
        }

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

