@extends('layouts.admin')

@section('title', 'Dispatch – Orders')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Dispatch – Orders</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Orders</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Paid / Dispatch Orders</h3>
            <form method="GET" action="{{ route('admin.dispatch.orders.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="search" name="search" class="form-control form-control-sm" placeholder="Order #, tracking, customer..." value="{{ $search }}" style="min-width: 180px;">
                <select name="status" class="form-select form-select-sm" style="min-width: 140px;">
                    <option value="">All statuses</option>
                    <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="packed" {{ $statusFilter === 'packed' ? 'selected' : '' }}>Packed</option>
                    <option value="shipped" {{ $statusFilter === 'shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="delivered" {{ $statusFilter === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                @if($search || $statusFilter)
                    <a href="{{ route('admin.dispatch.orders.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>KD No</th>
                            <th>Customer Name</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ordersByCustomer as $userId => $customerOrders)
                        <tr class="table-secondary">
                            <td colspan="7" class="py-2">
                                <strong>{{ $customerOrders->first()->user?->name ?? 'Unknown' }}</strong>
                                <small class="text-muted ms-2">{{ $customerOrders->first()->user?->email }}</small>
                                <span class="badge bg-light text-dark ms-2">{{ $customerOrders->count() }} order(s)</span>
                            </td>
                        </tr>
                        @foreach($customerOrders as $order)
                        <tr>
                            <td>
                                <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong>
                                @if($order->tracking_number && $order->getRawOriginal('tracking_number'))
                                    <br><small class="text-muted">Track: {{ $order->tracking_number }}</small>
                                @endif
                            </td>
                            <td>{{ $order->kd_id ?? '—' }}</td>
                            <td>{{ $order->customer_name ?? $order->user?->name ?? '—' }}</td>
                            <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                            <td>
                                @if($order->status === 'paid')
                                    <span class="badge bg-info">Paid</span>
                                @elseif($order->status === 'packed')
                                    <span class="badge bg-primary">Packed</span>
                                @elseif($order->status === 'shipped')
                                    <span class="badge bg-warning text-dark">Shipped</span>
                                @elseif($order->status === 'delivered' || $order->status === 'completed')
                                    <span class="badge bg-success">Delivered</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                @endif
                            </td>
                            <td class="text-end">₦{{ number_format($order->subtotal, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.dispatch.orders.view', $order) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <a href="{{ route('admin.dispatch.orders.show', $order) }}" class="btn btn-sm btn-primary">Process</a>
                            </td>
                        </tr>
                        @endforeach
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">No orders to dispatch.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
