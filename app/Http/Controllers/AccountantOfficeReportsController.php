<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\KediKitPurchase;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\OrgUserScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AccountantOfficeReportsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role?->name === 'accountant', 403);

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::now()->endOfDay();

        $offices = OrgUserScope::orgOffices($user);
        $officeRows = $offices->map(fn (User $office) => $this->summarizeOffice(
            $office,
            OrgUserScope::extendWithDescendants([$office->id]),
            $from,
            $to
        ));

        $allowedUserIds = OrgUserScope::allowedUserIds($user);
        $totals = $this->summarizeOffice(null, $allowedUserIds, $from, $to);

        return view('admin.accountant.office-reports', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'officeRows' => $officeRows,
            'totals' => $totals,
            'scopeLabel' => 'Your organisation (HQ, Branch, Service Center & Annex)',
        ]);
    }

    /**
     * @param  list<int>|null  $scopeIds
     */
    private function summarizeOffice(?User $office, ?array $scopeIds, Carbon $from, Carbon $to): object
    {
        $paidStatuses = [
            Order::STATUS_PAID,
            Order::STATUS_PACKED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];

        $invoicesQuery = Invoice::query()
            ->where('status', Invoice::STATUS_PAID)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('stock_deducted_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('stock_deducted_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            });
        if ($scopeIds !== null) {
            $invoicesQuery->where(function ($q) use ($scopeIds) {
                $q->whereIn('user_id', $scopeIds)
                    ->orWhereIn('branch_user_id', $scopeIds);
            });
        }
        $invoiceTotal = (float) (clone $invoicesQuery)->sum('total');
        $invoiceCount = (int) (clone $invoicesQuery)->count();

        $ordersQuery = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->where(function ($q) use ($paidStatuses) {
                $q->whereIn('status', $paidStatuses)
                    ->orWhereNotNull('collected_at')
                    ->orWhereNotNull('payment_proof')
                    ->orWhereNotNull('collection_branch_id')
                    ->orWhereIn('payment_method', ['wallet', 'dpbv', 'kd_credit', 'split']);
            });
        if ($scopeIds !== null) {
            $ordersQuery->where(function ($q) use ($scopeIds) {
                $q->where(function ($owner) use ($scopeIds) {
                    $owner->whereNull('collection_branch_id')
                        ->where(function ($inner) use ($scopeIds) {
                            $inner->whereIn('user_id', $scopeIds)
                                ->orWhereIn('branch_user_id', $scopeIds);
                        });
                })->orWhereIn('collection_branch_id', $scopeIds);
            });
        }
        $shopOrders = (clone $ordersQuery)->get(['subtotal', 'shipping_cost']);
        $shopTotal = (float) $shopOrders->sum(fn (Order $order) => (float) $order->subtotal + (float) ($order->shipping_cost ?? 0));
        $shopCount = $shopOrders->count();

        $topupsQuery = WalletTransaction::query()
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('approved_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('approved_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            });
        if ($scopeIds !== null) {
            $topupsQuery->whereIn('user_id', $scopeIds);
        }
        $walletTopupTotal = (float) (clone $topupsQuery)->sum('amount');
        $walletTopupCount = (int) (clone $topupsQuery)->count();

        $kitsQuery = KediKitPurchase::query()
            ->whereIn('status', [KediKitPurchase::STATUS_APPROVED, KediKitPurchase::STATUS_COMPLETED])
            ->whereBetween('created_at', [$from, $to]);
        if ($scopeIds !== null) {
            $kitsQuery->where(function ($q) use ($scopeIds) {
                $q->whereIn('buyer_user_id', $scopeIds)
                    ->orWhereIn('seller_user_id', $scopeIds);
            });
        }
        $kitTotal = (float) (clone $kitsQuery)->sum('total_price');
        $kitCount = (int) (clone $kitsQuery)->count();

        $salesTotal = $invoiceTotal + $shopTotal + $kitTotal;
        $allMoneyIn = $salesTotal + $walletTopupTotal;

        return (object) [
            'office' => $office,
            'office_id' => $office?->id,
            'type' => $office ? OrgUserScope::orgUnitLabel($office) : 'All',
            'name' => $office?->name ?? 'All offices',
            'email' => $office?->email,
            'invoice_total' => $invoiceTotal,
            'invoice_count' => $invoiceCount,
            'shop_total' => $shopTotal,
            'shop_count' => $shopCount,
            'kit_total' => $kitTotal,
            'kit_count' => $kitCount,
            'wallet_topup_total' => $walletTopupTotal,
            'wallet_topup_count' => $walletTopupCount,
            'sales_total' => $salesTotal,
            'all_money_in' => $allMoneyIn,
        ];
    }
}
