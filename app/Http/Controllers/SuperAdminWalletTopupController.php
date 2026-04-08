<?php

namespace App\Http\Controllers;

use App\Mail\WalletTopupApprovedMail;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SuperAdminWalletTopupController extends Controller
{
    /**
     * Include all users created under the given IDs (recursive), so e.g. Distributor/Cashier
     * top-ups appear for HQ/Branch/SC approvers.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function extendScopeWithDescendants(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        do {
            $added = User::query()
                ->whereIn('created_by_user_id', $ids)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->all();
            $ids = array_merge($ids, $added);
        } while ($added !== []);

        return $ids;
    }

    /**
     * Get user IDs that the current user is allowed to see (headquarters, branch, or service_center scope).
     * Returns non-null for: headquarters, branch, service_center, or accountant created by HQ/branch/service_center.
     */
    private function getHeadquartersScopeUserIds(Request $request): ?array
    {
        $user = $request->user();
        if ($user->role?->name === 'headquarters') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();

            return $this->extendScopeWithDescendants($ids);
        }
        if ($user->role?->name === 'branch') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'accountant']);
                        });
                })
                ->pluck('id')
                ->all();

            return $this->extendScopeWithDescendants($ids);
        }
        if ($user->role?->name === 'service_center') {
            $ids = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['annex', 'dispatch', 'accountant']);
                        });
                })
                ->pluck('id')
                ->all();

            return $this->extendScopeWithDescendants($ids);
        }
        if ($user->role?->name === 'accountant' && $user->created_by_user_id) {
            $creator = User::with('role')->find($user->created_by_user_id);
            if ($creator && $creator->role?->name === 'headquarters') {
                $hqId = $creator->id;
                $ids = User::where('id', $hqId)
                    ->orWhere(function ($q) use ($hqId) {
                        $q->where('created_by_user_id', $hqId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['service_center', 'annex', 'branch']);
                            });
                    })
                    ->pluck('id')
                    ->all();

                return $this->extendScopeWithDescendants($ids);
            }
            if ($creator && $creator->role?->name === 'branch') {
                $branchId = $creator->id;
                $ids = User::where('id', $branchId)
                    ->orWhere(function ($q) use ($branchId) {
                        $q->where('created_by_user_id', $branchId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['service_center', 'annex', 'accountant']);
                            });
                    })
                    ->pluck('id')
                    ->all();

                return $this->extendScopeWithDescendants($ids);
            }
            if ($creator && $creator->role?->name === 'service_center') {
                $scId = $creator->id;
                $ids = User::where('id', $scId)
                    ->orWhere(function ($q) use ($scId) {
                        $q->where('created_by_user_id', $scId)
                            ->whereHas('role', function ($r) {
                                $r->whereIn('name', ['annex', 'dispatch', 'accountant']);
                            });
                    })
                    ->pluck('id')
                    ->all();

                return $this->extendScopeWithDescendants($ids);
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

        if ($allowedUserIds !== null && ! in_array($tx->user_id, $allowedUserIds)) {
            abort(403, 'You can only approve wallet top-ups for your headquarters scope (Headquarters account and its Service Center, Annex, Branch users).');
        }

        if ($tx->status !== WalletTransaction::STATUS_PENDING) {
            return back()->with('error', 'This top-up is not pending.');
        }

        $emailPayload = null;
        DB::transaction(function () use ($tx, &$emailPayload) {
            $user = $tx->user()->lockForUpdate()->first();

            $user->increment('wallet_balance', (float) $tx->amount);
            $balanceAfter = (float) $user->fresh()->wallet_balance;

            $tx->update([
                'status' => WalletTransaction::STATUS_ACCEPTED,
                'approved_at' => now(),
                'balance_after' => $balanceAfter,
                'reference' => $tx->reference ?: ('Top-up accepted'),
            ]);

            $emailPayload = [
                'user' => $user,
                'amount' => (float) $tx->amount,
                'date' => now()->toDateString(),
                'transactionId' => (int) $tx->id,
                'balance' => (float) $balanceAfter,
            ];
        });

        if ($emailPayload && ($emailPayload['user']->email ?? null)) {
            try {
                Mail::to($emailPayload['user']->email)->send(new WalletTopupApprovedMail(
                    $emailPayload['user'],
                    $emailPayload['amount'],
                    $emailPayload['date'],
                    $emailPayload['transactionId'],
                    $emailPayload['balance'],
                ));
            } catch (\Throwable $e) {
                \Log::error('Wallet top-up email failed for '.($emailPayload['user']->email ?? 'unknown').': '.$e->getMessage());
            }
        }

        return back()->with('success', 'Top-up accepted and wallet credited.');
    }

    public function reject(Request $request, WalletTransaction $tx)
    {
        $allowedUserIds = $this->getHeadquartersScopeUserIds($request);

        if ($allowedUserIds !== null && ! in_array($tx->user_id, $allowedUserIds)) {
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
