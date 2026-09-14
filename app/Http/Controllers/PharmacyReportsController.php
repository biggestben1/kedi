<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Expenditure;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\User;
use App\Support\OrgUserScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PharmacyReportsController extends Controller
{
    private const PAID_STATUSES = [Order::STATUS_PAID, Order::STATUS_COMPLETED];

    /** Get allowed user IDs for headquarters, branch, or service_center scope. Null = all users. */
    private function getAllowedUserIdsForReports(?\App\Models\User $user, ?int $officeId = null): ?array
    {
        if (! $user) {
            return null;
        }
        $role = $user->role?->name ?? '';

        if ($role === 'accountant') {
            return OrgUserScope::scopeForOfficeFilter($user, $officeId);
        }

        if ($officeId !== null) {
            return OrgUserScope::scopeForOfficeFilter($user, $officeId);
        }
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
            ->with(['order.user', 'order.collectionBranch'])
            ->whereHas('order', function ($q) use ($from, $to, $customerId, $paymentMethod, $allowedUserIds) {
                $this->applyReportedOrderScope($q, $from, $to, $customerId, $paymentMethod, $allowedUserIds);
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
                'order_id' => $item->order->id,
                'invoice_number' => $item->order->invoice_number ?: '#' . $item->order->id,
                'order_date' => $item->order->created_at,
                'customer_name' => $item->order->customer_name ?: ($item->order->user?->name ?? '—'),
                'product_name' => $item->product_name,
                'quantity_sold' => $qty,
                'selling_price' => $sellingPrice,
                'line_total' => (float) $item->line_total,
                'discount' => 0,
                'profit' => $profit,
                'payment_status' => $item->order->collected_at ? 'collected' : $item->order->status,
                'payment_method' => $item->order->paymentLabel(),
                'payment_proof' => $item->order->payment_proof,
                'collection_branch' => $item->order->collectionBranch?->name,
                'invoice_url' => $item->order->collection_branch_id ? route('collection-centers.invoice', $item->order) : null,
                'proof_url' => $item->order->payment_proof && $item->order->collection_branch_id
                    ? route('collection-centers.proof.show', $item->order)
                    : null,
            ]);
        }

        return $lines;
    }

    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        $user = $request->user();
        $officeId = $request->filled('office_id') ? (int) $request->office_id : null;
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($user, $officeId);
        $offices = OrgUserScope::orgOffices($user);
        $selectedOffice = $officeId ? $offices->firstWhere('id', $officeId) : null;
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
            $this->applyReportedOrderScope($q, $from, $to, null, null, $allowedUserIds);
        })
            ->select('product_name', 'item_code', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('product_name', 'item_code')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get();

        $customerReportQuery = Order::query();
        $this->applyReportedOrderScope($customerReportQuery, $from, $to, null, null, $allowedUserIds);
        $customerReport = $customerReportQuery
            ->select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(subtotal) as total_spent'))
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('total_spent')
            ->limit(50)
            ->get();

        $ordersForPLQuery = Order::query();
        $this->applyReportedOrderScope($ordersForPLQuery, $from, $to, null, null, $allowedUserIds);
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
        // Costs & expenses (Expenditures)
        $expenditures = Expenditure::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('date')
            ->limit(500)
            ->get();
        $directCosts = $expenditures->where('cost_type', 'direct')->sum('amount');
        $operatingExpenses = $expenditures->where('cost_type', '!=', 'direct')->sum('amount');

        // Net Profit = Sales - COGS - Direct Costs - Operating Expenses
        $netProfitPL = $totalSalesPL - $totalCostPL - $directCosts - $operatingExpenses;

        // Assets & Journal
        $assets = Asset::query()->orderBy('name')->limit(500)->get();
        $journalEntries = JournalEntry::query()
            ->with('lines')
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->orderByDesc('entry_date')
            ->limit(200)
            ->get();

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
            Order::PAYMENT_DPBV => 'DPBV',
            'kd_credit' => 'KD Credit',
            'split' => 'Split',
        ];

        $paymentOrdersQuery = Order::with(['user', 'collectionBranch'])
            ->where(function ($q) {
                $q->whereNotNull('collection_branch_id')
                    ->orWhereNotNull('payment_proof')
                    ->orWhereNotNull('payment_breakdown');
            });
        $this->applyReportedOrderScope($paymentOrdersQuery, $from, $to, $customerId, $paymentMethod, $allowedUserIds);
        $paymentOrders = $paymentOrdersQuery->orderByDesc('created_at')->limit(200)->get();

        // Purchase report (line-level, filter by date range)
        $purchaseReportLines = PurchaseItem::with('purchase.supplier')
            ->whereHas('purchase', fn($q) => $q->whereBetween('purchase_date', [$from, $to]))
            ->orderByDesc('id')
            ->get();

        // All invoices made in date range (scoped by HQ/branch/SC/annex when applicable)
        $invoiceStatus = trim((string) $request->query('invoice_status', ''));
        $invoicesQuery = Invoice::with(['user', 'items'])
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()]);
        if ($allowedUserIds !== null) {
            $invoicesQuery->where(function ($q) use ($allowedUserIds) {
                $q->whereIn('user_id', $allowedUserIds)
                    ->orWhereIn('branch_user_id', $allowedUserIds);
            });
        }
        if ($customerId) {
            $invoicesQuery->where('user_id', $customerId);
        }
        if ($invoiceStatus !== '' && in_array($invoiceStatus, ['draft', 'sent', 'paid', 'overdue', 'cancelled'], true)) {
            $invoicesQuery->where('status', $invoiceStatus);
        }
        if ($paymentMethod) {
            $invoicesQuery->where('payment_method', $paymentMethod);
        }
        $invoices = $invoicesQuery
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(50, ['*'], 'invoice_page')
            ->withQueryString();

        $invoiceStatusCounts = Invoice::query()
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->when($allowedUserIds !== null, function ($q) use ($allowedUserIds) {
                $q->where(function ($inner) use ($allowedUserIds) {
                    $inner->whereIn('user_id', $allowedUserIds)
                        ->orWhereIn('branch_user_id', $allowedUserIds);
                });
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('admin.pharmacy.reports', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'categoryId' => $categoryId,
            'productId' => $productId,
            'customerId' => $customerId,
            'paymentMethod' => $paymentMethod,
            'offices' => $offices,
            'officeId' => $officeId,
            'selectedOffice' => $selectedOffice,
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
            'directCostsPL' => $directCosts,
            'operatingExpensesPL' => $operatingExpenses,
            'netProfitPL' => $netProfitPL,
            'purchaseReportLines' => $purchaseReportLines,
            'expenditures' => $expenditures,
            'assets' => $assets,
            'journalEntries' => $journalEntries,
            'invoices' => $invoices,
            'paymentOrders' => $paymentOrders,
            'invoiceStatus' => $invoiceStatus,
            'invoiceStatusCounts' => $invoiceStatusCounts,
            'activeTab' => $request->query('tab', 'sales'),
        ]);
    }

    private function applyReportedOrderScope($query, Carbon $from, Carbon $to, $customerId, $paymentMethod, ?array $allowedUserIds): void
    {
        $query->whereBetween('created_at', [$from, $to])
            ->where(function ($q) {
                $q->whereIn('status', self::PAID_STATUSES)
                    ->orWhereNotNull('collected_at')
                    ->orWhereNotNull('payment_proof')
                    ->orWhereNotNull('collection_branch_id')
                    ->orWhereIn('payment_method', ['wallet', 'dpbv', 'kd_credit', 'split']);
            });

        if ($customerId) {
            $query->where('user_id', $customerId);
        }
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }
        if ($allowedUserIds !== null) {
            $query->where(function ($q) use ($allowedUserIds) {
                $q->whereIn('user_id', $allowedUserIds)
                    ->orWhereIn('branch_user_id', $allowedUserIds)
                    ->orWhereIn('collection_branch_id', $allowedUserIds);
            });
        }
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $filters = $this->parseFilters($request);
        $officeId = $request->filled('office_id') ? (int) $request->office_id : null;
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($request->user(), $officeId);
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
        $officeId = $request->filled('office_id') ? (int) $request->office_id : null;
        $filters['allowedUserIds'] = $this->getAllowedUserIdsForReports($request->user(), $officeId);
        $salesLines = $this->buildSalesLines($filters);
        $filename = 'pharmacy-sales-report-' . $filters['from']->format('Y-m-d') . '-to-' . $filters['to']->format('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($salesLines) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice Number', 'Date', 'Customer', 'Product Name', 'Quantity Sold', 'Selling Price', 'Discount', 'Profit', 'Payment', 'Payment Status', 'Collection Center', 'Proof of Payment']);
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
                    $row->payment_method,
                    $row->payment_status,
                    $row->collection_branch ?? '',
                    $row->payment_proof ? 'Yes' : '',
                ]);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
