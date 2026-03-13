<?php

namespace App\Http\Controllers;

use App\Models\BackOrder;
use App\Models\User;
use Illuminate\Http\Request;

class BackOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $isHeadquarters = $user->role?->name === 'headquarters';
        $isBranch = $user->role?->name === 'branch';
        $isServiceCenter = $user->role?->name === 'service_center';
        $isAnnex = $user->role?->name === 'annex';

        $query = BackOrder::with('invoice', 'user', 'product')
            ->where('status', BackOrder::STATUS_PENDING)
            ->where('quantity_pending', '>', 0);

        if ($isHeadquarters) {
            $allowedUserIds = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['branch', 'annex', 'service_center']));
                })
                ->pluck('id')
                ->all();
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($isBranch) {
            $allowedUserIds = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['annex', 'service_center']));
                })
                ->pluck('id')
                ->all();
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($isServiceCenter) {
            $allowedUserIds = User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['annex', 'dispatch', 'accountant']));
                })
                ->pluck('id')
                ->all();
            $query->whereIn('user_id', $allowedUserIds);
        }

        if ($isAnnex) {
            $query->where('user_id', $user->id);
        }

        $backOrders = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.back-orders.index', [
            'backOrders' => $backOrders,
        ]);
    }
}
