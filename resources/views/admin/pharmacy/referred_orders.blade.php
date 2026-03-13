@extends('layouts.admin')

@section('title', 'Referred Orders')

@section('content')
<div class="page-header">
    <h1 class="page-title">Referred Orders</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Referred Orders</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
        <div class="card bg-primary img-card box-primary-shadow">
            <div class="card-body">
                <div class="d-flex">
                    <div class="text-white">
                        <h2 class="mb-0 number-font">{{ $stats['total_orders'] }}</h2>
                        <p class="text-white mb-0">Total Referred Orders</p>
                    </div>
                    <div class="ms-auto"> <i class="fe fe-shopping-cart text-white fs-30 me-2 mt-2"></i> </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
        <div class="card bg-secondary img-card box-secondary-shadow">
            <div class="card-body">
                <div class="d-flex">
                    <div class="text-white">
                        <h2 class="mb-0 number-font">₦{{ number_format($stats['total_sales'], 2) }}</h2>
                        <p class="text-white mb-0">Total Referred Sales</p>
                    </div>
                    <div class="ms-auto"> <i class="fe fe-dollar-sign text-white fs-30 me-2 mt-2"></i> </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
        <div class="card bg-success img-card box-success-shadow">
            <div class="card-body">
                <div class="d-flex">
                    <div class="text-white">
                        <h2 class="mb-0 number-font">{{ $stats['unique_customers'] }}</h2>
                        <p class="text-white mb-0">Unique Customers</p>
                    </div>
                    <div class="ms-auto"> <i class="fe fe-users text-white fs-30 me-2 mt-2"></i> </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Referred Products</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom" id="product-sales-table">
                        <thead>
                            <tr>
                                <th class="wd-15p border-bottom-0">Product Name</th>
                                <th class="wd-15p border-bottom-0">Item Code</th>
                                <th class="wd-15p border-bottom-0">Qty Sold</th>
                                <th class="wd-25p border-bottom-0">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(array_slice($productSales, 0, 10) as $sale)
                                <tr>
                                    <td>{{ $sale['product_name'] }}</td>
                                    <td>{{ $sale['item_code'] }}</td>
                                    <td>{{ $sale['total_qty'] }}</td>
                                    <td>₦{{ number_format($sale['total_amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No product data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Referral Order History</h3>
                <span>Referral Code: <strong>{{ auth()->user()->service_center_code }}</strong></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Service Center</th>
                                <th>Customer (Account)</th>
                                <th>Items Purchased</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td><strong>{{ $order->invoice_number }}</strong></td>
                                    <td>
                                        @if($order->sc_referral_code)
                                            {{ $scNames[$order->sc_referral_code] ?? 'Unknown Service Center' }}<br>
                                            <small class="text-muted">Code: {{ $order->sc_referral_code }}</small>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $order->user->name ?? 'User' }}<br>
                                        <small class="text-muted">{{ $order->customer_name }}</small>
                                    </td>
                                    <td>
                                        <ul class="list-unstyled mb-0">
                                            @foreach($order->items->take(3) as $item)
                                                <li><small>{{ $item->quantity }}x {{ $item->product_name }}</small></li>
                                            @endforeach
                                            @if($order->items->count() > 3)
                                                <li><small class="text-info">+ {{ $order->items->count() - 3 }} more items</small></li>
                                            @endif
                                        </ul>
                                    </td>
                                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                                    <td>₦{{ number_format($order->subtotal, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $order->status === 'paid' ? 'success' : 
                                            ($order->status === 'pending' ? 'warning' : 
                                            ($order->status === 'cancelled' ? 'danger' : 'secondary')) 
                                        }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-primary btn-sm">
                                            <i class="fe fe-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No referred orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
