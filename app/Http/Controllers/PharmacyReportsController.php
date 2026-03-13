<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PharmacyReportsController extends Controller
{
    private const PAID_STATUSES = [Order::STATUS_PAID, Order::STATUS_COMPLETED];

    /** Get allowed user IDs for headquarters, branch, or service_center scope. Null = all users. */
    private function getAllowedUserIdsForReports(?\App\Models\User $user): ?array
    {
        if (! $user) {
            return null;
        }
        $role = $user->role?->name ?? '';
        if ($role === 'headquarters') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['service_center', 'annex', 'branch']));
                })
                ->pluck('id')
                ->all();
        }
        if ($role === 'branch') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['service_center', 'annex', 'accountant']));
                })
                ->pluck('id')
                ->all();
        }
        if ($role === 'service_center') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['annex', 'accountant', 'dispatch']));
                })
                ->pluck('id')
                ->all();
        }
        if ($role === 'annex') {
            return User::where('id', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->where('created_by_user_id', $user->id)
                        ->whereHas('role', fn ($r) => $r->whereIn('name', ['accountant', 'dispatch']));
                })
                ->pluck('id')
                ->all();
        }

        return null;
    }

    /** Parse request filters into from, to, categoryId, productId, customerId, paymentMethod */
    private function parseFilters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : Carbon::now()->endOfDay();
        return [
            'from' => $from,
            'to' => $to,
            'categoryId' => $request->query('category_id'),
            'productId' => $request->query('product_id'),
            'customerId' => $request->query('customer_id'),
            'paymentMethod' => $request->query('payment_method'),
        ];
    }

    /** Build sales report as line-level rows (one per order item) with filters. */
    private function buildSalesLines(array $filters): \Illuminate\Support\Collection
    {
        $from = $filters['from'];
        $to = $filters['to'];
        $categoryId = $filters['categoryId'];
        $productId = $filters['productId'];
        $customerId = $filters['customerId'];
        $paymentMethod = $filters['paymentMethod'];
        $allowedUserIds = $filters['allowedUserIds'] ?? null;

        $query = OrderItem::query()
            ->with(['order.user'])
            ->whereHas('order', function ($q) use ($from, $to, $customerId, $paymentMethod, $allowedUserIds) {
                $q->whereIn('status', self::PAID_STATUSES)
                    ->whereBetween('created_at', [$from, $to]);
                if ($customerId) {
                    $q->where('user_id', $customerId);
                }
                if ($paymentMethod) {
                    $q->where('payment_method', $paymentMethod);
                }
                if ($allowedUserIds !== null) {
                    $q->whereIn('user_id', $allowedUserIds);
                }
            });

        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $query->where('item_code', $product->item_code);
            }
        }

        $items = $query->orderByDesc('id')->get();
        $productByCode = Product::with('category')->get()->keyBy('item_code');
        $lines = collect();

        foreach ($items as $item) {
            $product = $productByCode->get($item->item_code);
            if ($categoryId && (!$product || (int) $product->category_id !== (int) $categoryId)) {
                continue;
            }
            $cost = $product && $product->cost_price !== null ? (float) $product->cost_price : 0;
            $sellingPrice = (float) $item->unit_price;
            $qty = (int) $item->quantity;
            $profit = ($sellingPrice - $cost) * $qty;
            $lines->push((object) [
                'invoice_number' => $item->order->invoice_number ?: '#' . $item->order->id,
                'order_date' => $item->order->created_at,
                'customer_name' => $item->order->user?->name ?? '—',
                'product_name' => $item->product_name,
                'quantity_sold' => $qty,
                'selling_price' => $sellingPrice,
                'line_total' => (float) $item->line_total,
                'discount' => 0,
                'profit' => $profit,
                'payment_status' => $item->order->status,
                'payment_method' => $item->order->payment_method,
            ]);
        }

        return $lines;
    }

    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $user = $request->user();
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($user);
        $from = $filters['from'];
        $to = $filters['to'];
        $categoryId = $filters['categoryId'];
        $productId = $filters['productId'];
        $customerId = $filters['customerId'];
        $paymentMethod = $filters['paymentMethod'];

        $salesLines = $this->buildSalesLines($filters);
        $salesLinesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $salesLines->forPage($request->integer('sales_page', 1), 50),
            $salesLines->count(),
            50,
            $request->integer('sales_page', 1),
            ['path' => $request->url(), 'pageName' => 'sales_page']
        );
        $salesLinesPaginated->appends($request->except('sales_page'));

        // Inventory: stock report
        $stockProducts = Product::with('category')
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($productId, fn($q) => $q->where('id', $productId))
            ->orderBy('name')
            ->get();

        $expiryProducts = Product::whereNotNull('expiry_date')->orderBy('expiry_date')->get();
        $lowStockProducts = Product::whereRaw('min_stock > 0 AND stock <= min_stock')->orderBy('stock')->get();

        $allowedUserIds = $filters['allowedUserIds'] ?? null;
        $topSelling = OrderItem::whereHas('order', function ($q) use ($from, $to, $allowedUserIds) {
            $q->whereIn('status', self::PAID_STATUSES)->whereBetween('created_at', [$from, $to]);
            if ($allowedUserIds !== null) {
                $q->whereIn('user_id', $allowedUserIds);
            }
        })
            ->select('product_name', 'item_code', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('product_name', 'item_code')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();

        $customerReportQuery = Order::whereIn('status', self::PAID_STATUSES)->whereBetween('created_at', [$from, $to]);
        if ($allowedUserIds !== null) {
            $customerReportQuery->whereIn('user_id', $allowedUserIds);
        }
        $customerReport = $customerReportQuery
            ->select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(subtotal) as total_spent'))
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('total_spent')
            ->limit(50)
            ->get();

        $ordersForPLQuery = Order::whereIn('status', self::PAID_STATUSES)->whereBetween('created_at', [$from, $to]);
        if ($allowedUserIds !== null) {
            $ordersForPLQuery->whereIn('user_id', $allowedUserIds);
        }
        $ordersForPL = $ordersForPLQuery->with('items')->get();
        $totalSalesPL = $ordersForPL->sum('subtotal');
        $totalCostPL = 0;
        foreach ($ordersForPL as $order) {
            foreach ($order->items as $item) {
                $product = Product::where('item_code', $item->item_code)->first();
                $cost = $product && $product->cost_price !== null ? (float) $product->cost_price : 0;
                $totalCostPL += $cost * $item->quantity;
            }
        }
        $netProfitPL = $totalSalesPL - $totalCostPL;

        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get(['id', 'name', 'item_code']);
        $customersQuery = User::whereHas('orders');
        if ($allowedUserIds !== null) {
            $customersQuery->whereIn('id', $allowedUserIds);
        }
        $customers = $customersQuery->orderBy('name')->get(['id', 'name', 'email']);
        $paymentMethods = [
            Order::PAYMENT_WALLET => 'Wallet',
            Order::PAYMENT_PAY_ON_DELIVERY => 'Pay on Delivery',
        ];

        // Purchase report (line-level, filter by date range)
        $purchaseReportLines = PurchaseItem::with('purchase.supplier')
            ->whereHas('purchase', fn($q) => $q->whereBetween('purchase_date', [$from, $to]))
            ->orderByDesc('id')
            ->get();

        return view('admin.pharmacy.reports', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'categoryId' => $categoryId,
            'productId' => $productId,
            'customerId' => $customerId,
            'paymentMethod' => $paymentMethod,
            'categories' => $categories,
            'products' => $products,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'salesLines' => $salesLinesPaginated,
            'stockProducts' => $stockProducts,
            'expiryProducts' => $expiryProducts,
            'lowStockProducts' => $lowStockProducts,
            'topSelling' => $topSelling,
            'customerReport' => $customerReport,
            'totalSalesPL' => $totalSalesPL,
            'totalCostPL' => $totalCostPL,
            'netProfitPL' => $netProfitPL,
            'purchaseReportLines' => $purchaseReportLines,
        ]);
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $filters = $this->parseFilters($request);
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($request->user());
        $salesLines = $this->buildSalesLines($filters);
        $pdf = Pdf::loadView('admin.pharmacy.reports-pdf', [
            'salesLines' => $salesLines,
            'from' => $filters['from']->format('Y-m-d'),
            'to' => $filters['to']->format('Y-m-d'),
        ]);
        return $pdf->download('pharmacy-sales-report-' . $filters['from']->format('Y-m-d') . '-to-' . $filters['to']->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $filters = $this->parseFilters($request);
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($request->user());
        $salesLines = $this->buildSalesLines($filters);
        $filename = 'pharmacy-sales-report-' . $filters['from']->format('Y-m-d') . '-to-' . $filters['to']->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($salesLines) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice Number', 'Date', 'Customer', 'Product Name', 'Quantity Sold', 'Selling Price', 'Discount', 'Profit', 'Payment Status']);
            foreach ($salesLines as $row) {
                fputcsv($out, [
                    $row->invoice_number,
                    $row->order_date->format('Y-m-d H:i'),
                    $row->customer_name,
                    $row->product_name,
                    $row->quantity_sold,
                    number_format($row->selling_price, 2),
                    $row->discount,
                    number_format($row->profit, 2),
                    $row->payment_status,
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
