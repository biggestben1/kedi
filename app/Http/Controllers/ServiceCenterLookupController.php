<?php

namespace App\Http\Controllers;

use App\Models\DpbvCollection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceCenterLookupController extends Controller
{
    public function resolve(Request $request)
    {
        $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $query = trim((string) $request->input('query'));
        if ($query === '') {
            return response()->json([
                'valid' => false,
                'message' => 'Enter Service Center code or name.',
            ]);
        }

        $variants = array_values(array_unique(array_filter([
            $query,
            strtoupper($query),
            strtolower($query),
        ], fn ($v) => $v !== '')));

        // 1) Try exact code match (fast + unambiguous)
        $user = User::whereIn('service_center_code', $variants)
            ->whereHas('role', function ($q) {
                $q->where('name', Role::SERVICE_CENTER);
            })
            ->first();

        // 2) Try exact name match (case-insensitive)
        if (! $user) {
            $user = User::whereHas('role', function ($q) {
                    $q->where('name', Role::SERVICE_CENTER);
                })
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($query)])
                ->first();
        }

        // 3) Try partial name match; if single result, accept it
        if (! $user) {
            $matches = User::whereHas('role', function ($q) {
                    $q->where('name', Role::SERVICE_CENTER);
                })
                ->where('name', 'like', '%'.$query.'%')
                ->limit(5)
                ->get();

            if ($matches->count() === 1) {
                $user = $matches->first();
            } elseif ($matches->count() > 1) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Multiple Service Centers match this name. Please enter the code.',
                    'matches' => $matches->map(fn (User $u) => [
                        'name' => $u->name,
                        'code' => $u->service_center_code,
                    ])->values(),
                ]);
            }
        }

        if (! $user || ! $user->service_center_code) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid Service Center code or name.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'name' => $user->name,
            'code' => $user->service_center_code,
        ]);
    }

    private function effectiveDpbvQuery(User $user)
    {
        $query = DpbvCollection::query()->where('user_id', $user->id);

        $serviceCenterCode = trim((string) ($user->service_center_code ?? ''));
        if (($user->role?->name ?? null) === Role::SERVICE_CENTER && $serviceCenterCode !== '') {
            $scVariants = array_values(array_unique([
                $serviceCenterCode,
                strtoupper($serviceCenterCode),
                strtolower($serviceCenterCode),
            ]));

            $query->orWhere(function ($q) use ($scVariants) {
                $q->whereNull('user_id')->whereIn('sc', $scVariants);
            });
        }

        return $query;
    }

    public function balances(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $code = trim((string) $request->input('code'));
        $variants = array_values(array_unique(array_filter([
            $code,
            strtoupper($code),
            strtolower($code),
        ], fn ($v) => $v !== '')));

        $user = User::whereIn('service_center_code', $variants)
            ->whereHas('role', function ($query) {
                $query->where('name', Role::SERVICE_CENTER);
            })
            ->first();

        if (! $user) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid Service Center code.',
            ]);
        }

        $walletBalance = (float) ($user->wallet_balance ?? 0);
        $kediCreditBalance = (float) ($user->kedi_credit_balance ?? 0);
        $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;

        return response()->json([
            'valid' => true,
            'name' => $user->name,
            'wallet_balance' => $walletBalance,
            'kedi_credit_balance' => $kediCreditBalance,
            'dpbv' => $totalDpbv,
            'dpbv_naira' => $dpbvNairaEquivalent,
        ]);
    }
}

