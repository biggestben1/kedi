@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $wholesaleOnly ? 'Wholesale Dashboard' : ($serviceCenterOnly ?? false ? 'Service Center Dashboard' : ($annexOnly ?? false ? 'Annex Dashboard' : 'Dashboard')) }}</h1>
        <h3 class="text-muted mb-0">Welcome, <strong>{{ auth()->user()->name }}</strong></h3>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $wholesaleOnly ? 'Wholesale Dashboard' : 'Dashboard' }}</li>
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
                                        <li><a href="{{ in_array(auth()->user()->role?->name ?? '', ['service_center', 'annex']) ? route('admin.products.index') : route('admin.products.edit', $p) }}">{{ $p->name }}</a> — {{ $p->expiry_date?->format('M d, Y') }}</li>
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
                                        <li><a href="{{ in_array(auth()->user()->role?->name ?? '', ['service_center', 'annex']) ? route('admin.products.index') : route('admin.products.edit', $p) }}">{{ $p->name }}</a></li>
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
                                        <li><a href="{{ in_array(auth()->user()->role?->name ?? '', ['service_center', 'annex']) ? route('admin.products.index') : route('admin.products.edit', $p) }}">{{ $p->name }}</a> — {{ $p->stock }} left (min: {{ $p->min_stock }})</li>
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

    {{-- D. Branch Overview (hidden for Branch/Service Center/Annex — they are under a branch) --}}
    @unless(($branchOnly ?? false) || ($serviceCenterOnly ?? false) || ($annexOnly ?? false))
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Branch</h5>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Branches</h6>
                    <h3 class="mb-0">{{ $totalBranches }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Buying Branches</h3>
                </div>
                <div class="card-body p-0">
                    @if($topBuyingBranches->isEmpty())
                        <p class="text-muted p-4 mb-0">No orders yet.</p>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Branch</th>
                                    <th class="text-end">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBuyingBranches as $row)
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
    @endunless

    {{-- D.1. Top Performing Branches --}}
    @unless(($branchOnly ?? false) || ($serviceCenterOnly ?? false) || ($annexOnly ?? false))
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Top Performing Branches</h5>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Branch Performance Rankings</h3>
                </div>
                <div class="card-body p-0">
                    @if($topPerformingBranches->isEmpty())
                        <p class="text-muted p-4 mb-0">No branch performance data yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Branch</th>
                                        <th class="text-end">Total Sales</th>
                                        <th class="text-end">Total Orders</th>
                                        <th class="text-end">Avg Order Value</th>
                                        <th class="text-end">This Month Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topPerformingBranches as $index => $branch)
                                        <tr>
                                            <td>
                                                @if($index === 0)
                                                    <span class="badge bg-warning text-dark">🥇 1st</span>
                                                @elseif($index === 1)
                                                    <span class="badge bg-secondary">🥈 2nd</span>
                                                @elseif($index === 2)
                                                    <span class="badge bg-danger">🥉 3rd</span>
                                                @else
                                                    <span class="text-muted">#{{ $index + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $branch->user?->name ?? '—' }}</strong>
                                                <br><small class="text-muted">{{ $branch->user?->email ?? '—' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">₦{{ number_format($branch->total_sales ?? 0, 0) }}</strong>
                                            </td>
                                            <td class="text-end">{{ number_format($branch->total_orders ?? 0) }}</td>
                                            <td class="text-end">₦{{ number_format($branch->avg_order_value ?? 0, 0) }}</td>
                                            <td class="text-end">
                                                <strong class="text-primary">₦{{ number_format($branch->this_month_sales ?? 0, 0) }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endunless

    {{-- D.2. Service Center Performance Rankings (hidden for Service Center/Annex — they see their own stats in summary) --}}
    @unless(($serviceCenterOnly ?? false) || ($annexOnly ?? false))
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Service Center Performance Rankings</h5>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Service Center Performance Rankings</h3>
                </div>
                <div class="card-body p-0">
                    @if($topPerformingServiceCenters->isEmpty())
                        <p class="text-muted p-4 mb-0">No service center performance data yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Service Center</th>
                                        <th class="text-end">Total Sales</th>
                                        <th class="text-end">Total Orders</th>
                                        <th class="text-end">Avg Order Value</th>
                                        <th class="text-end">This Month Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topPerformingServiceCenters as $index => $serviceCenter)
                                        <tr>
                                            <td>
                                                @if($index === 0)
                                                    <span class="badge bg-warning text-dark">🥇 1st</span>
                                                @elseif($index === 1)
                                                    <span class="badge bg-secondary">🥈 2nd</span>
                                                @elseif($index === 2)
                                                    <span class="badge bg-danger">🥉 3rd</span>
                                                @else
                                                    <span class="text-muted">#{{ $index + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $serviceCenter->user?->name ?? '—' }}</strong>
                                                <br><small class="text-muted">{{ $serviceCenter->user?->email ?? '—' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">₦{{ number_format($serviceCenter->total_sales ?? 0, 0) }}</strong>
                                            </td>
                                            <td class="text-end">{{ number_format($serviceCenter->total_orders ?? 0) }}</td>
                                            <td class="text-end">₦{{ number_format($serviceCenter->avg_order_value ?? 0, 0) }}</td>
                                            <td class="text-end">
                                                <strong class="text-primary">₦{{ number_format($serviceCenter->this_month_sales ?? 0, 0) }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endunless

    {{-- D.3. Annex Performance Rankings (hidden for Annex — they see their own stats in summary) --}}
    @unless($annexOnly ?? false)
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Annex Performance Rankings</h5>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Annex Performance Rankings</h3>
                </div>
                <div class="card-body p-0">
                    @if($topPerformingAnnexes->isEmpty())
                        <p class="text-muted p-4 mb-0">No annex performance data yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Annex</th>
                                        <th class="text-end">Total Sales</th>
                                        <th class="text-end">Total Orders</th>
                                        <th class="text-end">Avg Order Value</th>
                                        <th class="text-end">This Month Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topPerformingAnnexes as $index => $annex)
                                        <tr>
                                            <td>
                                                @if($index === 0)
                                                    <span class="badge bg-warning text-dark">🥇 1st</span>
                                                @elseif($index === 1)
                                                    <span class="badge bg-secondary">🥈 2nd</span>
                                                @elseif($index === 2)
                                                    <span class="badge bg-danger">🥉 3rd</span>
                                                @else
                                                    <span class="text-muted">#{{ $index + 1 }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $annex->user?->name ?? '—' }}</strong>
                                                <br><small class="text-muted">{{ $annex->user?->email ?? '—' }}</small>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">₦{{ number_format($annex->total_sales ?? 0, 0) }}</strong>
                                            </td>
                                            <td class="text-end">{{ number_format($annex->total_orders ?? 0) }}</td>
                                            <td class="text-end">₦{{ number_format($annex->avg_order_value ?? 0, 0) }}</td>
                                            <td class="text-end">
                                                <strong class="text-primary">₦{{ number_format($annex->this_month_sales ?? 0, 0) }}</strong>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endunless

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

    {{-- F. DPBV Spending Overview (All Accounts) - Hidden for Annex --}}
    @if(auth()->user()->role?->name !== 'annex')
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">DPBV Spending Overview <small class="text-muted">(All Accounts)</small></h5>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-info">
                <div class="card-body">
                    <h6 class="text-muted">DPBV Spent Today</h6>
                    <h3 class="mb-0 text-info">{{ number_format($dpbvSpentToday ?? 0, 2) }}</h3>
                    <small class="text-muted">₦{{ number_format($dpbvSpentTodayNaira ?? 0, 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-info">
                <div class="card-body">
                    <h6 class="text-muted">DPBV Spent This Week</h6>
                    <h3 class="mb-0 text-info">{{ number_format($dpbvSpentThisWeek ?? 0, 2) }}</h3>
                    <small class="text-muted">₦{{ number_format($dpbvSpentThisWeekNaira ?? 0, 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-info">
                <div class="card-body">
                    <h6 class="text-muted">DPBV Spent This Month</h6>
                    <h3 class="mb-0 text-info">{{ number_format($dpbvSpentThisMonth ?? 0, 2) }}</h3>
                    <small class="text-muted">₦{{ number_format($dpbvSpentThisMonthNaira ?? 0, 2) }}</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Total DPBV Spent</h6>
                    <h3 class="mb-0 text-danger">{{ number_format($totalDpbvSpent ?? 0, 2) }}</h3>
                    <small class="text-muted">₦{{ number_format($totalDpbvSpentNaira ?? 0, 2) }}</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- G. KD Registration Credit Overview --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">KD Registration Credit Overview</h5>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Credit Balance</h6>
                    <h3 class="mb-0 text-success">₦{{ number_format($totalCreditBalance ?? 0, 2) }}</h3>
                    <small class="text-muted">All KD registrations</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Credit Used Today</h6>
                    <h3 class="mb-0 text-warning">₦{{ number_format($creditUsedToday ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Credit Used This Week</h6>
                    <h3 class="mb-0 text-warning">₦{{ number_format($creditUsedThisWeek ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Credit Used This Month</h6>
                    <h3 class="mb-0 text-warning">₦{{ number_format($creditUsedThisMonth ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card overflow-hidden border-danger">
                <div class="card-body">
                    <h6 class="text-muted">Total Credit Used</h6>
                    <h3 class="mb-0 text-danger">₦{{ number_format($totalCreditUsed ?? 0, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- H. Top KD Registrations by Credit Balance --}}
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top KD Registrations by Credit Balance</h3>
                </div>
                <div class="card-body p-0">
                    @if(($topKdByCredit ?? collect())->isEmpty())
                        <p class="text-muted p-4 mb-0">No KD registrations with credit balance.</p>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>KD NO</th>
                                    <th>Name</th>
                                    <th class="text-end">Credit Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topKdByCredit as $kd)
                                    <tr>
                                        <td><strong>{{ $kd['kd_no'] }}</strong></td>
                                        <td><strong>{{ $kd['full_name'] }}</strong></td>
                                        <td class="text-end">
                                            <strong class="text-success">₦{{ number_format($kd['balance'], 2) }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Credit Transactions</h3>
                </div>
                <div class="card-body p-0">
                    @if(($recentCreditTransactions ?? collect())->isEmpty())
                        <p class="text-muted p-4 mb-0">No credit transactions yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>KD NO</th>
                                        <th>Type</th>
                                        <th class="text-end">Amount</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCreditTransactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                            <td><strong>{{ $transaction->kdRegistration->kd_no ?? '—' }}</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                                    {{ ucfirst($transaction->type) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="{{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->type === 'credit' ? '+' : '-' }}₦{{ number_format($transaction->amount, 2) }}
                                                </span>
                                            </td>
                                            <td>{{ $transaction->createdBy->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- I. Charts Section (wholesale sales when wholesale_staff) --}}
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
