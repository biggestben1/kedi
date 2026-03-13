<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\DpbvCollection;
use App\Models\KdRegistration;
use App\Models\KdRegistrationCredit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();

        $paidStatuses = [Order::STATUS_PAID, Order::STATUS_COMPLETED];
        $wholesaleOnly = $request->user()?->role?->name === 'wholesale_staff';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';

        // Get allowed user IDs for headquarters (HQ + their branch/annex/service_center) or branch (branch + their annex/service_center/accountant) or service_center (self + annex/accountant/dispatch) or annex (self + accountant/dispatch)
        $allowedUserIds = null;
        if ($headquartersOnly) {
            $ownerId = $request->user()->id;
            $allowedUserIds = User::where('id', $ownerId)
                ->orWhere(function ($q) use ($ownerId) {
                    $q->where('created_by_user_id', $ownerId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'branch']);
                        });
                })
                ->pluck('id')
                ->all();
        }
        if ($branchOnly) {
            $ownerId = $request->user()->id;
            $allowedUserIds = User::where('id', $ownerId)
                ->orWhere(function ($q) use ($ownerId) {
                    $q->where('created_by_user_id', $ownerId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['service_center', 'annex', 'accountant']);
                        });
                })
                ->pluck('id')
                ->all();
        }
        if ($serviceCenterOnly) {
            $ownerId = $request->user()->id;
            $allowedUserIds = User::where('id', $ownerId)
                ->orWhere(function ($q) use ($ownerId) {
                    $q->where('created_by_user_id', $ownerId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['annex', 'accountant', 'dispatch']);
                        });
                })
                ->pluck('id')
                ->all();
        }
        if ($annexOnly) {
            $ownerId = $request->user()->id;
            $allowedUserIds = User::where('id', $ownerId)
                ->orWhere(function ($q) use ($ownerId) {
                    $q->where('created_by_user_id', $ownerId)
                        ->whereHas('role', function ($r) {
                            $r->whereIn('name', ['accountant', 'dispatch']);
                        });
                })
                ->pluck('id')
                ->all();
        }

        $orderQuery = Order::whereIn('status', $paidStatuses);
        if ($wholesaleOnly) {
            $orderQuery->wholesale();
        }
        if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
            $orderQuery->whereIn('user_id', $allowedUserIds);
        }

        // Sales Summary
        $salesToday = (clone $orderQuery)->whereDate('created_at', $today)->sum('subtotal');
        $salesThisWeek = (clone $orderQuery)->where('created_at', '>=', $startOfWeek)->sum('subtotal');
        $salesThisMonth = (clone $orderQuery)->where('created_at', '>=', $startOfMonth)->sum('subtotal');

        $ordersToday = (clone $orderQuery)->whereDate('created_at', $today)->count();
        $ordersThisMonth = (clone $orderQuery)->where('created_at', '>=', $startOfMonth)->count();
        $totalOrders = (clone $orderQuery)->count();
        $avgOrderValue = $totalOrders > 0 ? (clone $orderQuery)->avg('subtotal') : 0;

        // Profit (approximate: sum of (unit_price - cost) per item)
        $ordersForProfit = (clone $orderQuery)->with('items')->get();
        $totalProfit = 0;
        foreach ($ordersForProfit as $order) {
            foreach ($order->items as $item) {
                $product = Product::where('item_code', $item->item_code)->first();
                $cost = $product && $product->cost_price !== null ? (float) $product->cost_price : 0;
                $totalProfit += ($item->unit_price - $cost) * $item->quantity;
            }
        }

        // Inventory Summary (only for super_admin; wholesale view skips these in template)
        $totalProducts = Product::count();
        $lowStockProducts = Product::whereRaw('min_stock > 0 AND stock <= min_stock')->count();
        $outOfStockProducts = Product::where('stock', 0)->count();
        $expiringSoonDays = 30;
        $expiringSoonProducts = Product::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays($expiringSoonDays)])
            ->count();
        $expiredProducts = Product::whereNotNull('expiry_date')->where('expiry_date', '<', $today)->count();

        $alertsExpiringSoon = Product::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $today->copy()->addDays($expiringSoonDays)])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();
        $alertsOutOfStock = Product::where('stock', 0)->orderBy('name')->limit(10)->get();
        $alertsLowStock = Product::whereRaw('min_stock > 0 AND stock <= min_stock AND stock > 0')
            ->orderBy('stock')
            ->limit(10)
            ->get();

        // Branch overview
        $branchesQuery = User::whereHas('orders')->whereHas('role', fn ($r) => $r->where('name', 'branch'));
        if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
            $branchesQuery->whereIn('id', $allowedUserIds);
        }
        $totalBranches = $branchesQuery->distinct()->count('users.id');

        $topBuyingBranchesQuery = Order::whereIn('status', $paidStatuses)
            ->whereHas('user', function ($q) use ($headquartersOnly, $branchOnly, $serviceCenterOnly, $annexOnly, $allowedUserIds) {
                $q->whereHas('role', fn ($r) => $r->where('name', 'branch'));
                if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                    $q->whereIn('id', $allowedUserIds);
                }
            });
        $topBuyingBranches = $topBuyingBranchesQuery
            ->select('user_id', DB::raw('SUM(subtotal) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('user')
            ->get();

        // Top performing branches (by total sales, order count, and this month's performance)
        $topPerformingBranchesQuery = Order::whereIn('status', $paidStatuses)
            ->whereHas('user', function ($q) use ($headquartersOnly, $branchOnly, $serviceCenterOnly, $annexOnly, $allowedUserIds) {
                $q->whereHas('role', fn ($r) => $r->where('name', 'branch'));
                if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                    $q->whereIn('id', $allowedUserIds);
                }
            });
        
        $startOfMonthFormatted = $startOfMonth->format('Y-m-d H:i:s');
        $topPerformingBranches = $topPerformingBranchesQuery
            ->select('user_id', 
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('AVG(subtotal) as avg_order_value'),
                DB::raw("SUM(CASE WHEN created_at >= '{$startOfMonthFormatted}' THEN subtotal ELSE 0 END) as this_month_sales")
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('user')
            ->get();

        // Top performing service centers (by total sales, order count, and this month's performance)
        $topPerformingServiceCentersQuery = Order::whereIn('status', $paidStatuses)
            ->whereHas('user', function ($q) use ($headquartersOnly, $branchOnly, $serviceCenterOnly, $annexOnly, $allowedUserIds) {
                $q->whereHas('role', fn ($r) => $r->where('name', 'service_center'));
                if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                    $q->whereIn('id', $allowedUserIds);
                }
            });
        
        $topPerformingServiceCenters = $topPerformingServiceCentersQuery
            ->select('user_id', 
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('AVG(subtotal) as avg_order_value'),
                DB::raw("SUM(CASE WHEN created_at >= '{$startOfMonthFormatted}' THEN subtotal ELSE 0 END) as this_month_sales")
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('user')
            ->get();

        // Top performing annexes (by total sales, order count, and this month's performance)
        $topPerformingAnnexesQuery = Order::whereIn('status', $paidStatuses)
            ->whereHas('user', function ($q) use ($headquartersOnly, $branchOnly, $serviceCenterOnly, $annexOnly, $allowedUserIds) {
                $q->whereHas('role', fn ($r) => $r->where('name', 'annex'));
                if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                    $q->whereIn('id', $allowedUserIds);
                }
            });
        
        $topPerformingAnnexes = $topPerformingAnnexesQuery
            ->select('user_id', 
                DB::raw('SUM(subtotal) as total_sales'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('AVG(subtotal) as avg_order_value'),
                DB::raw("SUM(CASE WHEN created_at >= '{$startOfMonthFormatted}' THEN subtotal ELSE 0 END) as this_month_sales")
            )
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('user')
            ->get();

        // Sales trend last 14 days
        $salesTrend = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $trendQuery = Order::whereIn('status', $paidStatuses)->whereDate('created_at', $date);
            if ($wholesaleOnly) {
                $trendQuery->wholesale();
            }
            if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                $trendQuery->whereIn('user_id', $allowedUserIds);
            }
            $salesTrend[] = [
                'date' => $date->format('M d'),
                'sales' => (float) $trendQuery->sum('subtotal'),
            ];
        }

        // Top selling products (from orders only)
        $topSellingProductsQuery = OrderItem::whereHas('order', function ($q) use ($paidStatuses, $wholesaleOnly, $headquartersOnly, $branchOnly, $serviceCenterOnly, $annexOnly, $allowedUserIds) {
            $q->whereIn('status', $paidStatuses);
            if ($wholesaleOnly) {
                $q->wholesale();
            }
            if (($headquartersOnly || $branchOnly || $serviceCenterOnly || $annexOnly) && $allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
        });
        $topSellingProducts = $topSellingProductsQuery
            ->select('product_name', 'item_code', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('product_name', 'item_code')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // DPBV Spending Summary - All accounts
        $dpbvSpendingQuery = DpbvCollection::where('dpbv', '<', 0);
        // Show spending for all accounts (no filtering by user_id)
        
        $totalDpbvSpent = abs((float) (clone $dpbvSpendingQuery)->sum('dpbv'));
        $totalDpbvSpentNaira = ($totalDpbvSpent * 0.95) * 990;
        $dpbvSpentToday = abs((float) (clone $dpbvSpendingQuery)->whereDate('record_date', $today)->sum('dpbv'));
        $dpbvSpentThisWeek = abs((float) (clone $dpbvSpendingQuery)->where('record_date', '>=', $startOfWeek)->sum('dpbv'));
        $dpbvSpentThisMonth = abs((float) (clone $dpbvSpendingQuery)->where('record_date', '>=', $startOfMonth)->sum('dpbv'));
        $dpbvSpentTodayNaira = ($dpbvSpentToday * 0.95) * 990;
        $dpbvSpentThisWeekNaira = ($dpbvSpentThisWeek * 0.95) * 990;
        $dpbvSpentThisMonthNaira = ($dpbvSpentThisMonth * 0.95) * 990;

        // KD Registration Credit Summary
        $totalCreditBalance = 0;
        $totalCreditUsed = 0;
        $creditUsedToday = 0;
        $creditUsedThisWeek = 0;
        $creditUsedThisMonth = 0;
        $topKdByCredit = collect();
        $recentCreditTransactions = collect();

        try {
            $allKdRegistrations = KdRegistration::with('credits')->get();
            foreach ($allKdRegistrations as $reg) {
                $balance = $reg->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                $totalCreditBalance += max(0, $balance); // Only count positive balances
            }

            // Credit used (debits) statistics
            $creditUsedQuery = KdRegistrationCredit::where('type', KdRegistrationCredit::TYPE_DEBIT);
            $totalCreditUsed = (float) $creditUsedQuery->sum('amount');
            $creditUsedToday = (float) (clone $creditUsedQuery)->whereDate('created_at', $today)->sum('amount');
            $creditUsedThisWeek = (float) (clone $creditUsedQuery)->where('created_at', '>=', $startOfWeek)->sum('amount');
            $creditUsedThisMonth = (float) (clone $creditUsedQuery)->where('created_at', '>=', $startOfMonth)->sum('amount');

            // Top KD Registrations by Credit Balance
            $topKdByCredit = KdRegistration::with('credits')
                ->get()
                ->map(function ($reg) {
                    $balance = $reg->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                    return [
                        'kd_no' => $reg->kd_no,
                        'full_name' => $reg->full_name,
                        'balance' => $balance,
                    ];
                })
                ->filter(function ($item) {
                    return $item['balance'] > 0;
                })
                ->sortByDesc('balance')
                ->take(10)
                ->values();

            // Recent credit transactions
            $recentCreditTransactions = KdRegistrationCredit::with(['kdRegistration', 'createdBy'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            // If there's an error (e.g., table doesn't exist), use default values
            \Log::warning('Error loading KD credit data: ' . $e->getMessage());
        }


        return view('admin.pharmacy.dashboard', [
            'wholesaleOnly' => $wholesaleOnly,
            'headquartersOnly' => $headquartersOnly,
            'branchOnly' => $branchOnly,
            'serviceCenterOnly' => $serviceCenterOnly,
            'annexOnly' => $annexOnly,
            'salesToday' => $salesToday,
            'salesThisWeek' => $salesThisWeek,
            'salesThisMonth' => $salesThisMonth,
            'totalProfit' => $totalProfit,
            'ordersToday' => $ordersToday,
            'ordersThisMonth' => $ordersThisMonth,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'totalProducts' => $totalProducts,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'expiringSoonProducts' => $expiringSoonProducts,
            'expiredProducts' => $expiredProducts,
            'totalBranches' => $totalBranches,
            'topBuyingBranches' => $topBuyingBranches,
            'topPerformingBranches' => $topPerformingBranches,
            'topPerformingServiceCenters' => $topPerformingServiceCenters,
            'topPerformingAnnexes' => $topPerformingAnnexes,
            'alertsExpiringSoon' => $alertsExpiringSoon,
            'alertsOutOfStock' => $alertsOutOfStock,
            'alertsLowStock' => $alertsLowStock,
            'salesTrend' => $salesTrend,
            'topSellingProducts' => $topSellingProducts,
            'totalDpbvSpent' => $totalDpbvSpent,
            'totalDpbvSpentNaira' => $totalDpbvSpentNaira,
            'dpbvSpentToday' => $dpbvSpentToday,
            'dpbvSpentTodayNaira' => $dpbvSpentTodayNaira,
            'dpbvSpentThisWeek' => $dpbvSpentThisWeek,
            'dpbvSpentThisWeekNaira' => $dpbvSpentThisWeekNaira,
            'dpbvSpentThisMonth' => $dpbvSpentThisMonth,
            'dpbvSpentThisMonthNaira' => $dpbvSpentThisMonthNaira,
            'totalCreditBalance' => $totalCreditBalance,
            'totalCreditUsed' => $totalCreditUsed,
            'creditUsedToday' => $creditUsedToday,
            'creditUsedThisWeek' => $creditUsedThisWeek,
            'creditUsedThisMonth' => $creditUsedThisMonth,
            'topKdByCredit' => $topKdByCredit,
            'recentCreditTransactions' => $recentCreditTransactions,
        ]);
    }

    public function referredOrders(Request $request)
    {
        $user = $request->user();

        // Only allow SC, Branch, Annex, HQ, Super Admin
        if (
            ! $user->isServiceCenter()
            && ! $user->isBranch()
            && ! $user->isAnnex()
            && ! $user->isHeadquarters()
            && ! $user->isSuperAdmin()
        ) {
            abort(403);
        }

        // Only count real (paid/completed) referred orders
        $paidStatuses = [Order::STATUS_PAID, Order::STATUS_COMPLETED];
        $query = Order::whereIn('status', $paidStatuses);

        if ($user->isSuperAdmin()) {
            // All referred orders in the system
            $query->whereNotNull('sc_referral_code');
        } elseif ($user->isHeadquarters()) {
            // Headquarters: referred orders from all SCs under their branches
            $branchIds = User::where('created_by_user_id', $user->id)->pluck('id');
            $scCodes = User::whereIn('created_by_user_id', $branchIds)
                ->whereNotNull('service_center_code')
                ->pluck('service_center_code');

            $query->whereIn('sc_referral_code', $scCodes);
        } elseif ($user->isBranch()) {
            // Branch: referred orders from all Service Centers they created
            $scCodes = User::where('created_by_user_id', $user->id)
                ->whereNotNull('service_center_code')
                ->pluck('service_center_code');

            $query->whereIn('sc_referral_code', $scCodes);
        } elseif ($user->isServiceCenter() || $user->isAnnex()) {
            // Service Center / Annex: only their own referral code
            $query->where('sc_referral_code', $user->service_center_code);
        } else {
            // Fallback – should not happen because of the first guard
            abort(403);
        }

        $orders = (clone $query)
            ->with(['user', 'items'])
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get all referred orders for calculations (unpaginated total stats)
        $allReferredOrders = (clone $query)
            ->with('items')
            ->get();

        $stats = [
            'total_orders' => $allReferredOrders->count(),
            'total_sales' => $allReferredOrders->sum('subtotal'),
            'unique_customers' => $allReferredOrders->unique(function ($item) {
                return $item->user_id . '-' . $item->customer_name;
            })->count(),
        ];

        // Aggregate items sold
        $productSales = [];
        foreach ($allReferredOrders as $order) {
            foreach ($order->items as $item) {
                $key = $item->item_code;
                if (! isset($productSales[$key])) {
                    $productSales[$key] = [
                        'product_name' => $item->product_name,
                        'item_code' => $item->item_code,
                        'total_qty' => 0,
                        'total_amount' => 0,
                    ];
                }
                $productSales[$key]['total_qty'] += $item->quantity;
                $productSales[$key]['total_amount'] += $item->line_total;
            }
        }

        // Sort by quantity sold descending
        usort($productSales, fn ($a, $b) => ($b['total_qty'] ?? 0) <=> ($a['total_qty'] ?? 0));

        // Fetch Service Center names for display (especially useful for admins)
        $referralCodes = $allReferredOrders->pluck('sc_referral_code')->unique()->filter();
        $scNames = User::whereIn('service_center_code', $referralCodes)
            ->pluck('name', 'service_center_code');

        return view('admin.pharmacy.referred_orders', [
            'orders' => $orders,
            'stats' => $stats,
            'productSales' => $productSales,
            'scNames' => $scNames,
        ]);
    }
}
