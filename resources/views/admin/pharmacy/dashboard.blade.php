@extends('layouts.admin')

@section('title', 'Pharmacy Dashboard')

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $wholesaleOnly ? 'Wholesale Dashboard' : 'Pharmacy Dashboard' }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $wholesaleOnly ? 'Wholesale Dashboard' : 'Pharmacy Dashboard' }}</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$wholesaleOnly)
    {{-- Alerts & Notifications (super_admin only) --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning-transparent">
                    <h3 class="card-title mb-0"><i class="fe fe-alert-triangle me-2"></i>Alerts & Notifications</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($alertsExpiringSoon->isNotEmpty())
                            <div class="col-md-6 col-lg-3 mb-3">
                                <h6 class="text-danger">Drugs Expiring Soon (30 days)</h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($alertsExpiringSoon->take(5) as $p)
                                        <li><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a> — {{ $p->expiry_date?->format('M d, Y') }}</li>
                                    @endforeach
                                    @if($alertsExpiringSoon->count() > 5)
                                        <li class="text-muted">+{{ $alertsExpiringSoon->count() - 5 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        @if($alertsOutOfStock->isNotEmpty())
                            <div class="col-md-6 col-lg-3 mb-3">
                                <h6 class="text-danger">Out of Stock</h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($alertsOutOfStock->take(5) as $p)
                                        <li><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a></li>
                                    @endforeach
                                    @if($alertsOutOfStock->count() > 5)
                                        <li class="text-muted">+{{ $alertsOutOfStock->count() - 5 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        @if($alertsLowStock->isNotEmpty())
                            <div class="col-md-6 col-lg-3 mb-3">
                                <h6 class="text-warning">Low Stock (Reorder)</h6>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach($alertsLowStock->take(5) as $p)
                                        <li><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a> — {{ $p->stock }} left (min: {{ $p->min_stock }})</li>
                                    @endforeach
                                    @if($alertsLowStock->count() > 5)
                                        <li class="text-muted">+{{ $alertsLowStock->count() - 5 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        @if($alertsExpiringSoon->isEmpty() && $alertsOutOfStock->isEmpty() && $alertsLowStock->isEmpty())
                            <div class="col-12">
                                <p class="text-success mb-0">No critical alerts at the moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- A. Sales Summary (wholesale-only when wholesale_staff) --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">{{ $wholesaleOnly ? 'Wholesale Sales Summary' : 'Sales Summary' }}</h5>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Total Sales Today</h6>
                    <h3 class="mb-0">₦{{ number_format($salesToday, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Sales This Week</h6>
                    <h3 class="mb-0">₦{{ number_format($salesThisWeek, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Sales This Month</h6>
                    <h3 class="mb-0">₦{{ number_format($salesThisMonth, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Total Profit</h6>
                    <h3 class="mb-0 text-success">₦{{ number_format($totalProfit, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Total Orders</h6>
                    <h3 class="mb-0">{{ number_format($totalOrders) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Avg Order Value</h6>
                    <h3 class="mb-0">₦{{ number_format($avgOrderValue, 0) }}</h3>
                </div>
            </div>
        </div>
    </div>

    @if(!$wholesaleOnly)
    {{-- B. Inventory Summary (super_admin only) --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Inventory Summary</h5>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h6 class="text-muted">Total Products</h6>
                    <h3 class="mb-0">{{ $totalProducts }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card overflow-hidden border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Low Stock</h6>
                    <h3 class="mb-0 text-warning">{{ $lowStockProducts }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card overflow-hidden border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Out of Stock</h6>
                    <h3 class="mb-0 text-danger">{{ $outOfStockProducts }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card overflow-hidden border-info">
                <div class="card-body">
                    <h6 class="text-muted">Expiring Soon (30d)</h6>
                    <h3 class="mb-0 text-info">{{ $expiringSoonProducts }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card overflow-hidden border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Expired</h6>
                    <h3 class="mb-0 text-danger">{{ $expiredProducts }}</h3>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!$wholesaleOnly)
    {{-- C. Purchase Summary (super_admin only) --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Purchase Summary</h5>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Purchases Today</h6>
                    <h3 class="mb-0">—</h3>
                    <small class="text-muted">Add purchase module</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Pending POs</h6>
                    <h3 class="mb-0">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Supplier Payments</h6>
                    <h3 class="mb-0">—</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Outstanding Balance</h6>
                    <h3 class="mb-0">—</h3>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- D. Customer & Reseller Overview (wholesale: resellers/wholesale_staff only) --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">{{ $wholesaleOnly ? 'Resellers & Wholesale Buyers' : 'Customer & Reseller Overview' }}</h5>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">{{ $wholesaleOnly ? 'Resellers / Wholesale Buyers' : 'Total Customers' }}</h6>
                    <h3 class="mb-0">{{ $totalCustomers }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $wholesaleOnly ? 'Top Wholesale Buyers' : 'Top Buying Customers' }}</h3>
                </div>
                <div class="card-body p-0">
                    @if($topBuyingCustomers->isEmpty())
                        <p class="text-muted p-4 mb-0">No orders yet.</p>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-end">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBuyingCustomers as $row)
                                    <tr>
                                        <td>{{ $row->user?->name ?? '—' }} <small class="text-muted">({{ $row->user?->email }})</small></td>
                                        <td class="text-end">₦{{ number_format($row->total, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- E. Financial Overview (wholesale-only when wholesale_staff) --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">{{ $wholesaleOnly ? 'Wholesale Financial Overview' : 'Financial Overview' }}</h5>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3 class="mb-0 text-success">₦{{ number_format($salesThisMonth, 0) }}</h3>
                    <small class="text-muted">This month</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Net Profit</h6>
                    <h3 class="mb-0 text-primary">₦{{ number_format($totalProfit, 0) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Orders (Paid)</h6>
                    <h3 class="mb-0">{{ $totalOrders }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Pending Payments</h6>
                    <h3 class="mb-0">—</h3>
                    <small class="text-muted">From orders</small>
                </div>
            </div>
        </div>
    </div>

    {{-- G. Charts Section (wholesale sales when wholesale_staff) --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $wholesaleOnly ? 'Wholesale Sales Trend (Last 14 Days)' : 'Sales Trend (Last 14 Days)' }}</h3>
                </div>
                <div class="card-body">
                    <canvas id="salesTrendChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $wholesaleOnly ? 'Top Selling (Wholesale)' : 'Top Selling Products' }}</h3>
                </div>
                <div class="card-body p-0">
                    @if($topSellingProducts->isEmpty())
                        <p class="text-muted p-4 mb-0">No sales data yet.</p>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topSellingProducts as $row)
                                    <tr>
                                        <td>{{ $row->product_name }}</td>
                                        <td class="text-end">{{ number_format($row->total_qty) }}</td>
                                        <td class="text-end">₦{{ number_format($row->total_sales, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
(function() {
    var ctx = document.getElementById('salesTrendChart');
    if (!ctx) return;
    var data = @json($salesTrend);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(function(d) { return d.date; }),
            datasets: [{
                label: 'Sales (₦)',
                data: data.map(function(d) { return d.sales; }),
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
})();
    </script>
    @endpush
@endsection
