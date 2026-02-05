<?php

namespace App\Http\Controllers;

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

        $orderQuery = Order::whereIn('status', $paidStatuses);
        if ($wholesaleOnly) {
            $orderQuery->wholesale();
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

        // Customer / Reseller overview (wholesale: only resellers & wholesale_staff who ordered)
        $customersQuery = User::whereHas('orders');
        if ($wholesaleOnly) {
            $customersQuery->whereHas('role', fn ($r) => $r->whereIn('name', ['wholesale_staff', 'reseller']));
        }
        $totalCustomers = $customersQuery->distinct()->count('users.id');

        $topBuyingQuery = Order::whereIn('status', $paidStatuses);
        if ($wholesaleOnly) {
            $topBuyingQuery->wholesale();
        }
        $topBuyingCustomers = $topBuyingQuery
            ->select('user_id', DB::raw('SUM(subtotal) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
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
            $salesTrend[] = [
                'date' => $date->format('M d'),
                'sales' => (float) $trendQuery->sum('subtotal'),
            ];
        }

        // Top selling products (from orders only)
        $topSellingProductsQuery = OrderItem::whereHas('order', function ($q) use ($paidStatuses, $wholesaleOnly) {
            $q->whereIn('status', $paidStatuses);
            if ($wholesaleOnly) {
                $q->wholesale();
            }
        });
        $topSellingProducts = $topSellingProductsQuery
            ->select('product_name', 'item_code', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('product_name', 'item_code')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        return view('admin.pharmacy.dashboard', [
            'wholesaleOnly' => $wholesaleOnly,
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
            'totalCustomers' => $totalCustomers,
            'topBuyingCustomers' => $topBuyingCustomers,
            'alertsExpiringSoon' => $alertsExpiringSoon,
            'alertsOutOfStock' => $alertsOutOfStock,
            'alertsLowStock' => $alertsLowStock,
            'salesTrend' => $salesTrend,
            'topSellingProducts' => $topSellingProducts,
        ]);
    }
}
