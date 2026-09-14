@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Reports</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                @if(auth()->user()->role?->name === 'accountant')
                    <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.reports') }}">Reports</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.dashboard') }}">Dashboard</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">Reports</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->role?->name === 'accountant')
                <a href="{{ route('admin.accountant.office-reports', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary"><i class="fe fe-layers me-1"></i>Office Reports</a>
            @endif
            <a href="{{ route('admin.pharmacy.reports') }}" class="btn btn-outline-primary"><i class="fe fe-plus me-1"></i>Create new report</a>
            <a href="{{ route('admin.pharmacy.financial') }}" class="btn btn-outline-success"><i class="fe fe-dollar-sign me-1"></i>Financial Report</a>
            @php $exportQuery = request()->only(['from','to','category_id','product_id','customer_id','payment_method','office_id']); @endphp
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
                <input type="hidden" name="invoice_page" value="1">
                <input type="hidden" name="tab" id="report-active-tab" value="{{ $activeTab ?? 'sales' }}">
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
                @if(!empty($offices) && $offices->isNotEmpty())
                <div class="col-md-2">
                    <label class="form-label">Office</label>
                    <select name="office_id" class="form-select">
                        <option value="">All offices</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}" {{ (int) ($officeId ?? 0) === (int) $office->id ? 'selected' : '' }}>
                                {{ \App\Support\OrgUserScope::orgUnitLabel($office) }} — {{ $office->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Invoice Status</label>
                    <select name="invoice_status" class="form-select">
                        <option value="">All</option>
                        @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" {{ (string)($invoiceStatus ?? '') === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
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

    @php $tab = $activeTab ?? 'sales'; @endphp
    {{-- Tabs: Sales | Invoices | Inventory | Purchase | Costs | Journal | Assets | Payment | P&L | Product Performance | Customer | Batch --}}
    <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'sales' ? 'active' : '' }}" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'invoices' ? 'active' : '' }}" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab">
                Invoices
                <span class="badge bg-primary ms-1">{{ $invoices->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'inventory' ? 'active' : '' }}" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">Inventory</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="purchase-tab" data-bs-toggle="tab" data-bs-target="#purchase" type="button" role="tab">Purchase</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="costs-tab" data-bs-toggle="tab" data-bs-target="#costs" type="button" role="tab">Costs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="journal-tab" data-bs-toggle="tab" data-bs-target="#journal" type="button" role="tab">Journal</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assets-tab" data-bs-toggle="tab" data-bs-target="#assets" type="button" role="tab">Assets</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'payment' ? 'active' : '' }}" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">Payment</button>
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
        <div class="tab-pane fade {{ $tab === 'sales' ? 'show active' : '' }}" id="sales" role="tabpanel">
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
                                    <th>Payment</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesLines as $row)
                                    <tr>
                                        <td>
                                            @if(!empty($row->invoice_url))
                                                <a href="{{ $row->invoice_url }}" target="_blank">{{ $row->invoice_number }}</a>
                                            @else
                                                {{ $row->invoice_number }}
                                            @endif
                                        </td>
                                        <td>{{ $row->order_date->format('M d, Y H:i') }}</td>
                                        <td>{{ $row->customer_name }}</td>
                                        <td>{{ $row->product_name }}</td>
                                        <td class="text-end">{{ $row->quantity_sold }}</td>
                                        <td class="text-end">₦{{ number_format($row->selling_price, 0) }}</td>
                                        <td class="text-end">₦{{ number_format($row->discount, 0) }}</td>
                                        <td class="text-end">₦{{ number_format($row->profit, 0) }}</td>
                                        <td>
                                            {{ $row->payment_method }}
                                            <div class="small text-muted">{{ str_replace('_', ' ', $row->payment_status) }}</div>
                                            @if(!empty($row->collection_branch))
                                                <div class="small text-muted">{{ $row->collection_branch }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($row->proof_url))
                                                <a href="{{ $row->proof_url }}" target="_blank">View proof of payment</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center text-muted p-4">No sales in date range.</td></tr>
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

        {{-- A2. Invoices Report (all invoices made) --}}
        <div class="tab-pane fade {{ $tab === 'invoices' ? 'show active' : '' }}" id="invoices" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="card-title mb-0">Invoices</h3>
                        <div class="small text-muted mt-1">All invoices in the selected date range.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['draft' => 'secondary', 'sent' => 'info', 'paid' => 'success', 'overdue' => 'warning', 'cancelled' => 'danger'] as $statusKey => $badge)
                            <span class="badge bg-{{ $badge }}">
                                {{ ucfirst($statusKey) }}: {{ (int) ($invoiceStatusCounts[$statusKey] ?? 0) }}
                            </span>
                        @endforeach
                        @if(auth()->user()->role?->name !== 'accountant')
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-primary">Manage invoices</a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Created by</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    @php
                                        $statusBadge = match ($invoice->status) {
                                            'paid' => 'success',
                                            'sent' => 'info',
                                            'overdue' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</td>
                                        <td>
                                            {{ $invoice->customer_name ?: ($invoice->user?->name ?? '—') }}
                                            @if($invoice->customer_email)
                                                <div class="small text-muted">{{ $invoice->customer_email }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $invoice->user?->name ?? '—' }}</td>
                                        <td class="text-end">{{ $invoice->items->count() }}</td>
                                        <td class="text-end">₦{{ number_format((float) $invoice->subtotal, 0) }}</td>
                                        <td class="text-end">₦{{ number_format((float) $invoice->tax, 0) }}</td>
                                        <td class="text-end">₦{{ number_format((float) $invoice->discount + (float) ($invoice->coupon_discount_amount ?? 0), 0) }}</td>
                                        <td class="text-end fw-semibold">₦{{ number_format((float) $invoice->total, 0) }}</td>
                                        <td>{{ $invoice->payment_method ? str_replace('_', ' ', ucfirst($invoice->payment_method)) : '—' }}</td>
                                        <td><span class="badge bg-{{ $statusBadge }}">{{ ucfirst($invoice->status) }}</span></td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted p-4">
                                            No invoices in date range.
                                            @if(auth()->user()->role?->name !== 'accountant')
                                            <a href="{{ route('admin.invoices.create') }}">Create an invoice</a>.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($invoices->hasPages())
                    <div class="card-footer">{{ $invoices->appends(array_merge(request()->except('invoice_page'), ['tab' => 'invoices']))->links() }}</div>
                @endif
            </div>
        </div>

        {{-- B. Inventory Reports --}}
        <div class="tab-pane fade {{ $tab === 'inventory' ? 'show active' : '' }}" id="inventory" role="tabpanel">
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

        {{-- C2. Costs / Expenses --}}
        <div class="tab-pane fade" id="costs" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Costs & Expenses</h3>
                    <div class="small text-muted mt-1">Direct costs are tagged with cost_type = <code>direct</code>. Everything else is treated as operating expense.</div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-muted small">Direct Costs (Date Range)</div>
                                <div class="fs-5 fw-semibold">₦{{ number_format((float) ($directCostsPL ?? 0), 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-muted small">Operating Expenses (Date Range)</div>
                                <div class="fs-5 fw-semibold">₦{{ number_format((float) ($operatingExpensesPL ?? 0), 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-muted small">Total (Date Range)</div>
                                <div class="fs-5 fw-semibold">₦{{ number_format((float) (($directCostsPL ?? 0) + ($operatingExpensesPL ?? 0)), 0) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenditures as $e)
                                    <tr>
                                        <td>{{ $e->date?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $e->description }}</td>
                                        <td>{{ $e->category ?? '—' }}</td>
                                        <td>
                                            @if(($e->cost_type ?? '') === 'direct')
                                                <span class="badge bg-warning text-dark">Direct cost</span>
                                            @else
                                                <span class="badge bg-secondary">Expense</span>
                                            @endif
                                        </td>
                                        <td class="text-end">₦{{ number_format((float) $e->amount, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted p-4">No costs/expenses in date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- C3. Journal --}}
        <div class="tab-pane fade" id="journal" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Journal (Adjustments)</h3>
                    <div class="small text-muted mt-1">For error correction, provisions and accruals (debit/credit entries).</div>
                    <div class="mt-2">
                        <a href="{{ route('admin.pharmacy.journal.index') }}" class="btn btn-sm btn-outline-primary">Open Journal Module</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Memo</th>
                                    <th class="text-end">Total Debit</th>
                                    <th class="text-end">Total Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($journalEntries as $je)
                                    @php
                                        $totalDebit = (float) $je->lines->sum('debit');
                                        $totalCredit = (float) $je->lines->sum('credit');
                                    @endphp
                                    <tr>
                                        <td>{{ $je->entry_date?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $je->reference ?? '—' }}</td>
                                        <td>{{ $je->type ?? '—' }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($je->memo ?? '', 80) }}</td>
                                        <td class="text-end">₦{{ number_format($totalDebit, 0) }}</td>
                                        <td class="text-end">₦{{ number_format($totalCredit, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted p-4">No journal entries in date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- C4. Assets --}}
        <div class="tab-pane fade" id="assets" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Assets Register</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Asset</th>
                                    <th>Category</th>
                                    <th>Purchase Date</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Accum. Depreciation</th>
                                    <th class="text-end">Net Book Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $a)
                                    <tr>
                                        <td>{{ $a->name }}</td>
                                        <td>{{ $a->category ?? '—' }}</td>
                                        <td>{{ $a->purchase_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="text-end">₦{{ number_format((float) $a->cost, 0) }}</td>
                                        <td class="text-end">₦{{ number_format((float) $a->accumulated_depreciation, 0) }}</td>
                                        <td class="text-end">₦{{ number_format((float) ($a->net_book_value ?? 0), 0) }}</td>
                                        <td>{{ $a->status ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted p-4">No assets recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- D. Payment --}}
        <div class="tab-pane fade {{ $tab === 'payment' ? 'show active' : '' }}" id="payment" role="tabpanel">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Payments</h3>
                    <div class="small text-muted mt-1">Shop and collection-center payments in this date range, including proof of payment.</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Collection center</th>
                                    <th>Payment</th>
                                    <th class="text-end">Amount</th>
                                    <th>Proof</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentOrders as $order)
                                    <tr>
                                        <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            @if($order->collection_branch_id)
                                                <a href="{{ route('collection-centers.invoice', $order) }}" target="_blank">{{ $order->invoice_number ?: ('ORD-'.$order->id) }}</a>
                                            @else
                                                {{ $order->invoice_number ?: ('ORD-'.$order->id) }}
                                            @endif
                                        </td>
                                        <td>
                                            {{ $order->customer_name ?: ($order->user?->name ?? '—') }}
                                            @if($order->kd_id)
                                                <div class="small text-muted">{{ $order->kd_id }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $order->collectionBranch?->name ?: '—' }}</td>
                                        <td>
                                            {{ $order->paymentLabel() }}
                                            @include('collection-centers.payment-details', ['order' => $order])
                                        </td>
                                        <td class="text-end">₦{{ number_format($order->subtotal, 2) }}</td>
                                        <td>
                                            @if($order->payment_proof && $order->collection_branch_id)
                                                <a href="{{ route('collection-centers.proof.show', $order) }}" target="_blank">View proof of payment</a>
                                            @else
                                                <span class="text-muted">No proof</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted p-4">No payments in this date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
                        <tr><td>COGS (Cost of Goods Sold)</td><td class="text-end">₦{{ number_format($totalCostPL, 0) }}</td></tr>
                        <tr><td>Direct Costs (Other)</td><td class="text-end">₦{{ number_format((float) ($directCostsPL ?? 0), 0) }}</td></tr>
                        <tr><td>Operating Expenses</td><td class="text-end">₦{{ number_format((float) ($operatingExpensesPL ?? 0), 0) }}</td></tr>
                        <tr><td><strong>Net Profit</strong></td><td class="text-end {{ ($netProfitPL ?? 0) >= 0 ? 'text-success' : 'text-danger' }}"><strong>₦{{ number_format($netProfitPL, 0) }}</strong></td></tr>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabInput = document.getElementById('report-active-tab');
    var tabButtons = document.querySelectorAll('#reportTabs [data-bs-toggle="tab"]');
    tabButtons.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            var target = (e.target.getAttribute('data-bs-target') || '').replace('#', '');
            if (tabInput && target) {
                tabInput.value = target;
            }
        });
    });
});
</script>
@endpush
