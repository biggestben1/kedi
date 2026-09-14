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
use Illuminate\Support\Collection;

class PharmacyFinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        $officeId = $request->filled('office_id') ? (int) $request->office_id : null;
        $allowedUserIds = OrgUserScope::scopeForOfficeFilter($user, $officeId);
        $offices = OrgUserScope::orgOffices($user);
        $selectedOffice = $officeId ? $offices->firstWhere('id', $officeId) : null;

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();

        // --- Paid invoices ---
        $invoicesQuery = Invoice::with(['user.role', 'branchUser.role', 'items'])
            ->where('status', Invoice::STATUS_PAID)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                    ->orWhereBetween('stock_deducted_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('stock_deducted_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            });
        if ($allowedUserIds !== null) {
            $invoicesQuery->where(function ($q) use ($allowedUserIds) {
                $q->whereIn('user_id', $allowedUserIds)
                    ->orWhereIn('branch_user_id', $allowedUserIds);
            });
        }
        $paidInvoices = $invoicesQuery->orderByDesc('invoice_date')->orderByDesc('id')->get();

        // --- Shop orders (paid / fulfilled) ---
        $paidStatuses = [
            Order::STATUS_PAID,
            Order::STATUS_PACKED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];
        $ordersQuery = Order::with(['user.role', 'branchUser.role', 'collectionBranch.role'])
            ->where(function ($q) use ($from, $to, $paidStatuses) {
                $q->where(function ($paid) use ($from, $to, $paidStatuses) {
                    $paid->whereNull('collection_branch_id')
                        ->whereBetween('created_at', [$from, $to])
                        ->where(function ($inner) use ($paidStatuses) {
                            $inner->whereIn('status', $paidStatuses)
                                ->orWhereIn('payment_method', ['wallet', 'dpbv', 'kd_credit', 'split']);
                        });
                })->orWhere(function ($collected) use ($from, $to) {
                    $collected->whereNotNull('collected_at')
                        ->whereBetween('collected_at', [$from, $to]);
                });
            });
        if ($allowedUserIds !== null) {
            $ordersQuery->where(function ($q) use ($allowedUserIds) {
                $q->whereIn('user_id', $allowedUserIds)
                    ->orWhereIn('branch_user_id', $allowedUserIds)
                    ->orWhereIn('collection_branch_id', $allowedUserIds);
            });
        }
        $shopOrders = $ordersQuery->orderByDesc('created_at')->get();

        // --- Wallet top-ups accepted (money funded into wallets) ---
        $topupsQuery = WalletTransaction::with('user.role')
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->where('status', WalletTransaction::STATUS_ACCEPTED)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('approved_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to) {
                        $inner->whereNull('approved_at')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            });
        if ($allowedUserIds !== null) {
            $topupsQuery->whereIn('user_id', $allowedUserIds);
        }
        $walletTopups = $topupsQuery->orderByDesc('approved_at')->orderByDesc('id')->get();

        // --- Kit purchases approved/completed ---
        $kitsQuery = KediKitPurchase::with(['buyer.role', 'seller', 'kit'])
            ->whereIn('status', [KediKitPurchase::STATUS_APPROVED, KediKitPurchase::STATUS_COMPLETED])
            ->whereBetween('created_at', [$from, $to]);
        if ($allowedUserIds !== null) {
            $kitsQuery->where(function ($q) use ($allowedUserIds) {
                $q->whereIn('buyer_user_id', $allowedUserIds)
                    ->orWhereIn('seller_user_id', $allowedUserIds);
            });
        }
        $kitPurchases = $kitsQuery->orderByDesc('created_at')->get();

        $invoiceTotal = (float) $paidInvoices->sum('total');
        $shopTotal = (float) $shopOrders->sum(fn (Order $o) => (float) $o->subtotal + (float) ($o->shipping_cost ?? 0));
        $kitTotal = (float) $kitPurchases->sum('total_price');
        $walletTopupTotal = (float) $walletTopups->sum('amount');

        $salesTotal = $invoiceTotal + $shopTotal + $kitTotal;
        $allMoneyIn = $salesTotal + $walletTopupTotal;

        $byPaymentMethod = $this->buildPaymentMethodBreakdown($paidInvoices, $shopOrders);
        $byLocation = $this->buildLocationBreakdown($paidInvoices, $shopOrders, $walletTopups, $kitPurchases);
        $entries = $this->buildUnifiedEntries($paidInvoices, $shopOrders, $walletTopups, $kitPurchases);

        $scopeLabel = $this->scopeLabel($user, $allowedUserIds, $selectedOffice);

        return view('admin.pharmacy.financial-report', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'isToday' => $from->isToday() && $to->isToday(),
            'scopeLabel' => $scopeLabel,
            'offices' => $offices,
            'officeId' => $officeId,
            'selectedOffice' => $selectedOffice,
            'paidInvoices' => $paidInvoices,
            'shopOrders' => $shopOrders,
            'walletTopups' => $walletTopups,
            'kitPurchases' => $kitPurchases,
            'invoiceTotal' => $invoiceTotal,
            'shopTotal' => $shopTotal,
            'kitTotal' => $kitTotal,
            'walletTopupTotal' => $walletTopupTotal,
            'salesTotal' => $salesTotal,
            'allMoneyIn' => $allMoneyIn,
            'byPaymentMethod' => $byPaymentMethod,
            'byLocation' => $byLocation,
            'entries' => $entries,
        ]);
    }

    private function scopeLabel(User $user, ?array $allowedUserIds, ?User $selectedOffice = null): string
    {
        if ($selectedOffice) {
            return OrgUserScope::orgUnitLabel($selectedOffice).': '.$selectedOffice->name;
        }

        if ($allowedUserIds === null) {
            return 'All locations';
        }

        if ($user->role?->name === 'accountant') {
            return 'Your organisation (HQ, Branch, Service Center & Annex)';
        }

        return match ($user->role?->name) {
            'headquarters' => 'Headquarters and all branches, service centers & annexes',
            'branch' => 'This branch and its service centers & annexes',
            'service_center' => 'This service center and its annexes',
            'annex' => 'This annex and its staff',
            default => 'Scoped locations',
        };
    }

    private function buildPaymentMethodBreakdown(Collection $invoices, Collection $orders): Collection
    {
        $map = [];

        foreach ($invoices as $invoice) {
            $method = $invoice->payment_method ?: 'unspecified';
            if ($method === 'split' && is_array($invoice->payment_breakdown)) {
                foreach ($invoice->payment_breakdown as $key => $amount) {
                    if (in_array($key, ['total'], true) || ! is_numeric($amount) || (float) $amount <= 0) {
                        continue;
                    }
                    $map[$key] = ($map[$key] ?? 0) + (float) $amount;
                }
                continue;
            }
            $map[$method] = ($map[$method] ?? 0) + (float) $invoice->total;
        }

        foreach ($orders as $order) {
            $method = $order->payment_method ?: 'unspecified';
            $amount = (float) $order->subtotal + (float) ($order->shipping_cost ?? 0);
            if ($method === 'split' && is_array($order->payment_breakdown)) {
                foreach ($order->payment_breakdown as $key => $part) {
                    if (in_array($key, ['total', 'pos_machine', 'bank_account'], true) || ! is_numeric($part) || (float) $part <= 0) {
                        continue;
                    }
                    $map[$key] = ($map[$key] ?? 0) + (float) $part;
                }
                continue;
            }
            $map[$method] = ($map[$method] ?? 0) + $amount;
        }

        return collect($map)->sortDesc();
    }

    private function buildLocationBreakdown(
        Collection $invoices,
        Collection $orders,
        Collection $topups,
        Collection $kits
    ): Collection {
        $labels = [
            'headquarters' => 'Headquarters',
            'branch' => 'Branch',
            'service_center' => 'Service Center',
            'annex' => 'Annex',
            'other' => 'Other',
        ];
        $map = array_fill_keys(array_keys($labels), 0.0);

        foreach ($invoices as $invoice) {
            $owner = $invoice->branchUser ?? $invoice->user;
            $unit = OrgUserScope::resolveOrgUnit($owner);
            $map[$unit] = ($map[$unit] ?? 0) + (float) $invoice->total;
        }

        foreach ($orders as $order) {
            $owner = $order->collectionBranch ?? $order->branchUser ?? $order->user;
            $unit = OrgUserScope::resolveOrgUnit($owner);
            $amount = (float) $order->subtotal + (float) ($order->shipping_cost ?? 0);
            $map[$unit] = ($map[$unit] ?? 0) + $amount;
        }

        foreach ($topups as $tx) {
            $unit = OrgUserScope::resolveOrgUnit($tx->user);
            $map[$unit] = ($map[$unit] ?? 0) + (float) $tx->amount;
        }

        foreach ($kits as $kit) {
            $unit = OrgUserScope::resolveOrgUnit($kit->buyer);
            $map[$unit] = ($map[$unit] ?? 0) + (float) $kit->total_price;
        }

        return collect($map)
            ->filter(fn ($amount) => $amount > 0)
            ->mapWithKeys(fn ($amount, $key) => [($labels[$key] ?? ucfirst($key)) => $amount])
            ->sortDesc();
    }

    private function buildUnifiedEntries(
        Collection $invoices,
        Collection $orders,
        Collection $topups,
        Collection $kits
    ): Collection {
        $locationLabels = [
            'headquarters' => 'HQ',
            'branch' => 'Branch',
            'service_center' => 'SC',
            'annex' => 'Annex',
            'other' => 'Other',
        ];

        $rows = collect();

        foreach ($invoices as $invoice) {
            $owner = $invoice->branchUser ?? $invoice->user;
            $unit = OrgUserScope::resolveOrgUnit($owner);
            $rows->push((object) [
                'when' => $invoice->stock_deducted_at ?? $invoice->updated_at ?? $invoice->invoice_date,
                'source' => 'Invoice',
                'reference' => $invoice->invoice_number,
                'party' => $invoice->customer_name ?: ($invoice->user?->name ?? '—'),
                'location' => $locationLabels[$unit] ?? 'Other',
                'method' => $invoice->payment_method ?: '—',
                'amount' => (float) $invoice->total,
                'url' => route('admin.invoices.show', $invoice),
            ]);
        }

        foreach ($orders as $order) {
            $owner = $order->collectionBranch ?? $order->branchUser ?? $order->user;
            $unit = OrgUserScope::resolveOrgUnit($owner);
            $amount = (float) $order->subtotal + (float) ($order->shipping_cost ?? 0);
            $rows->push((object) [
                'when' => $order->collected_at ?? $order->created_at,
                'source' => $order->collection_branch_id ? 'Collection' : 'Shop',
                'reference' => $order->invoice_number ?: '#'.$order->id,
                'party' => $order->customer_name ?: ($order->user?->name ?? '—'),
                'location' => $order->collectionBranch?->name ?: ($locationLabels[$unit] ?? 'Other'),
                'method' => $order->paymentLabel(),
                'amount' => $amount,
                'url' => $order->collection_branch_id
                    ? route('collection-centers.invoice', $order)
                    : route('admin.dispatch.orders.show', $order),
                'proof_url' => $order->payment_proof && $order->collection_branch_id
                    ? route('collection-centers.proof.show', $order)
                    : null,
            ]);
        }

        foreach ($topups as $tx) {
            $unit = OrgUserScope::resolveOrgUnit($tx->user);
            $rows->push((object) [
                'when' => $tx->approved_at ?? $tx->updated_at ?? $tx->created_at,
                'source' => 'Wallet top-up',
                'reference' => $tx->reference ?: '#'.$tx->id,
                'party' => $tx->user?->name ?? '—',
                'location' => $locationLabels[$unit] ?? 'Other',
                'method' => 'wallet_topup',
                'amount' => (float) $tx->amount,
                'url' => route('admin.wallet_topups.approved'),
            ]);
        }

        foreach ($kits as $kit) {
            $unit = OrgUserScope::resolveOrgUnit($kit->buyer);
            $rows->push((object) [
                'when' => $kit->created_at,
                'source' => 'Kit purchase',
                'reference' => $kit->kit?->name ? $kit->kit->name.' ×'.$kit->quantity : '#'.$kit->id,
                'party' => $kit->buyer?->name ?? '—',
                'location' => $locationLabels[$unit] ?? 'Other',
                'method' => '—',
                'amount' => (float) $kit->total_price,
                'url' => route('admin.kedi-kits.purchase.index'),
            ]);
        }

        return $rows->sortByDesc(function ($row) {
            return $row->when ? Carbon::parse($row->when)->timestamp : 0;
        })->values();
    }
}
