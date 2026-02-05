@extends('layouts.admin')

@section('title', 'Pharmacy Reports')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Pharmacy Reports</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.dashboard') }}">Pharmacy Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Reports</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pharmacy.reports') }}" class="btn btn-outline-primary"><i class="fe fe-plus me-1"></i>Create new report</a>
            @php $exportQuery = request()->only(['from','to','category_id','product_id','customer_id','payment_method']); @endphp
            <a href="{{ route('admin.pharmacy.reports.export.pdf', $exportQuery) }}" class="btn btn-danger" target="_blank"><i class="fe fe-file-text me-1"></i>Export PDF</a>
            <a href="{{ route('admin.pharmacy.reports.export.excel', $exportQuery) }}" class="btn btn-success"><i class="fe fe-download me-1"></i>Export Excel (CSV)</a>
        </div>
    </div>

    {{-- Report Filters --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Report Filters</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pharmacy.reports') }}" class="row g-3">
                <input type="hidden" name="sales_page" value="1">
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ (string)$categoryId === (string)$c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ (string)$productId === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">All</option>
                        @foreach($customers as $u)
                            <option value="{{ $u->id }}" {{ (string)$customerId === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All</option>
                        @foreach($paymentMethods as $value => $label)
                            <option value="{{ $value }}" {{ (string)$paymentMethod === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.pharmacy.reports') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabs: Sales | Inventory | Purchase | Payment | P&L | Product Performance | Customer | Batch --}}
    <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">Inventory</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchase" type="button" role="tab">Purchase</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">Payment</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pl-tab" data-bs-toggle="tab" data-bs-target="#pl" type="button" role="tab">Profit & Loss</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="product-tab" data-bs-toggle="tab" data-bs-target="#product" type="button" role="tab">Product Performance</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab">Customer</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="batch-tab" data-bs-toggle="tab" data-bs-target="#batch" type="button" role="tab">Batch & Drug Tracking</button>
        </li>
    </ul>

    <div class="tab-content" id="reportTabsContent">
        {{-- A. Sales Report (line-level: Invoice, Product, Qty, Selling Price, Discount, Profit, Payment Status) --}}
        <div class="tab-pane fade show active" id="sales" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sales Report</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Product Name</th>
                                    <th class="text-end">Qty Sold</th>
                                    <th class="text-end">Selling Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Profit</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesLines as $row)
                                    <tr>
                                        <td>{{ $row->invoice_number }}</td>
                                        <td>{{ $row->order_date->format('M d, Y H:i') }}</td>
                                        <td>{{ $row->customer_name }}</td>
                                        <td>{{ $row->product_name }}</td>
                                        <td class="text-end">{{ $row->quantity_sold }}</td>
                                        <td class="text-end">₦{{ number_format($row->selling_price, 0) }}</td>
                                        <td class="text-end">₦{{ number_format($row->discount, 0) }}</td>
                                        <td class="text-end">₦{{ number_format($row->profit, 0) }}</td>
                                        <td><span class="badge bg-success">{{ $row->payment_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted p-4">No sales in date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($salesLines->hasPages())
                    <div class="card-footer">{{ $salesLines->links() }}</div>
                @endif
            </div>
        </div>

        {{-- B. Inventory Reports --}}
        <div class="tab-pane fade" id="inventory" role="tabpanel">
            <ul class="nav nav-pills mb-3">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#stock-report">Stock Report</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#expiry-report">Expiry Report</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#lowstock-report">Low Stock</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="stock-report">
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Product</th><th>Batch Number</th><th>Category</th><th class="text-end">Qty Available</th><th class="text-end">Cost Price</th><th class="text-end">Selling Price</th></tr></thead>
                                <tbody>
                                    @foreach($stockProducts as $p)
                                        <tr>
                                            <td>{{ $p->name }} @if($p->pack_size)<small class="text-muted">({{ $p->pack_size }})</small>@endif</td>
                                            <td>{{ $p->batch_number ?? '—' }}</td>
                                            <td>{{ $p->category?->name ?? '—' }}</td>
                                            <td class="text-end">{{ $p->stock }}</td>
                                            <td class="text-end">{{ $p->formatted_cost_price }}</td>
                                            <td class="text-end">{{ $p->formatted_price }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="expiry-report">
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Product</th><th>Batch</th><th>Expiry Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse($expiryProducts as $p)
                                        <tr>
                                            <td>{{ $p->name }}</td>
                                            <td>{{ $p->batch_number ?? '—' }}</td>
                                            <td>{{ $p->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                            <td>
                                                @if($p->expiry_date)
                                                    @if($p->expiry_date->isPast())
                                                        <span class="badge bg-danger">Expired</span>
                                                    @elseif(!$p->expiry_date->isPast() && $p->expiry_date->diffInDays(now()->startOfDay(), false) <= 30)
                                                        <span class="badge bg-warning">Expiring Soon</span>
                                                    @else
                                                        <span class="badge bg-secondary">OK</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted p-4">No expiry dates set.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="lowstock-report">
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Product</th><th>Min Stock</th><th class="text-end">Current Qty</th></tr></thead>
                                <tbody>
                                    @forelse($lowStockProducts as $p)
                                        <tr>
                                            <td>{{ $p->name }}</td>
                                            <td>{{ $p->min_stock }}</td>
                                            <td class="text-end">{{ $p->stock }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted p-4">No low stock items.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- C. Purchase Reports --}}
        <div class="tab-pane fade" id="purchase" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Purchase Report</h3>
                    <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New Purchase Invoice</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Supplier Name</th>
                                    <th>Purchase Date</th>
                                    <th>Product Purchased</th>
                                    <th class="text-end">Cost Price</th>
                                    <th>Payment Status</th>
                                    <th>Purchase Invoice</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseReportLines as $row)
                                    <tr>
                                        <td>{{ $row->purchase?->supplier?->name ?? '—' }}</td>
                                        <td>{{ $row->purchase?->purchase_date?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $row->product_name }}</td>
                                        <td class="text-end">₦{{ number_format($row->cost_price, 0) }}</td>
                                        <td>
                                            @if($row->purchase)
                                                @if($row->purchase->payment_status === 'paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($row->purchase->payment_status === 'partial')
                                                    <span class="badge bg-warning">Partial</span>
                                                @else
                                                    <span class="badge bg-secondary">Pending</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($row->purchase)
                                                <a href="{{ route('admin.purchases.edit', $row->purchase) }}">{{ $row->purchase->purchase_invoice ?: '#' . $row->purchase->id }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted p-4">No purchases in date range. <a href="{{ route('admin.purchases.create') }}">Create a purchase invoice</a>.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- D. Payment (placeholder) --}}
        <div class="tab-pane fade" id="payment" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-0">Payment reports (Customer Payments, Supplier Payments, Pending, Daily Cash Flow) will appear here when payment tracking is extended.</p>
                </div>
            </div>
        </div>

        {{-- E. Profit & Loss --}}
        <div class="tab-pane fade" id="pl" role="tabpanel">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Profit & Loss (Date Range)</h3></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><td>Total Sales</td><td class="text-end">₦{{ number_format($totalSalesPL, 0) }}</td></tr>
                        <tr><td>Total Purchase Cost</td><td class="text-end">₦{{ number_format($totalCostPL, 0) }}</td></tr>
                        <tr><td><strong>Net Profit</strong></td><td class="text-end text-success"><strong>₦{{ number_format($netProfitPL, 0) }}</strong></td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- F. Product Performance --}}
        <div class="tab-pane fade" id="product" role="tabpanel">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Top Selling Products</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Product</th><th class="text-end">Qty Sold</th><th class="text-end">Total Sales</th></tr></thead>
                        <tbody>
                            @forelse($topSelling as $row)
                                <tr>
                                    <td>{{ $row->product_name }}</td>
                                    <td class="text-end">{{ number_format($row->total_qty) }}</td>
                                    <td class="text-end">₦{{ number_format($row->total_sales, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted p-4">No data in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- G. Customer Report --}}
        <div class="tab-pane fade" id="customer" role="tabpanel">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Customer Purchase Summary</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Orders</th><th class="text-end">Total Spent</th></tr></thead>
                        <tbody>
                            @forelse($customerReport as $row)
                                <tr>
                                    <td>{{ $row->user?->name ?? '—' }} <small class="text-muted">({{ $row->user?->email }})</small></td>
                                    <td class="text-end">{{ $row->order_count }}</td>
                                    <td class="text-end">₦{{ number_format($row->total_spent, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted p-4">No orders in date range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- H. Batch & Drug Tracking (Pharmacy specific) --}}
        <div class="tab-pane fade" id="batch" role="tabpanel">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Batch & Drug Tracking</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Product</th><th>Batch Number</th><th>Expiry Date</th><th>Expiry Status</th><th>Category</th></tr></thead>
                        <tbody>
                            @forelse($expiryProducts as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ $p->batch_number ?? '—' }}</td>
                                    <td>{{ $p->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                    <td>
                                        @if($p->expiry_date)
                                            @if($p->expiry_date->isPast())
                                                <span class="badge bg-danger">Expired</span>
                                            @elseif($p->expiry_date->diffInDays(now()->startOfDay(), false) <= 30)
                                                <span class="badge bg-warning">Expiring Soon</span>
                                            @else
                                                <span class="badge bg-secondary">OK</span>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $p->category?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted p-4">No batch/expiry data. Add expiry date and batch to products.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
