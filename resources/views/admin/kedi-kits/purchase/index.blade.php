@extends('layouts.admin')

@section('title', 'My Kit Purchases')

@section('content')
<div class="page-header">
    <h1 class="page-title">My Kit Purchases</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kit Purchases</li>
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
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">My Kit Purchases</h3>
        <a href="{{ route('admin.kedi-kits.purchase.create') }}" class="btn btn-primary">
            <i class="fe fe-plus me-2"></i>Purchase Kits
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kit</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Seller</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->id }}</td>
                            <td>
                                <strong>Kit #{{ $purchase->kit->id }}</strong>
                                @if($purchase->kit->description)
                                    <br><small class="text-muted">{{ Str::limit($purchase->kit->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $purchase->kit->category === 'english' ? 'primary' : 'info' }}">
                                    {{ $purchase->kit->category_label }}
                                </span>
                            </td>
                            <td>{{ $purchase->quantity }}</td>
                            <td>₦{{ number_format($purchase->unit_price, 2) }}</td>
                            <td><strong>₦{{ number_format($purchase->total_price, 2) }}</strong></td>
                            <td>{{ $purchase->seller->name ?? 'N/A' }}</td>
                            <td>
                                @if($purchase->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($purchase->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($purchase->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif($purchase->status === 'completed')
                                    <span class="badge bg-info">Completed</span>
                                @endif
                            </td>
                            <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.kedi-kits.purchase.show', $purchase) }}" class="btn btn-sm btn-info">
                                    <i class="fe fe-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">No purchases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $purchases->links() }}
    </div>
</div>

@if($backOrders->count() > 0)
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">My Back Orders</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Back Order ID</th>
                        <th>Kit</th>
                        <th>Purchase ID</th>
                        <th>Quantity Pending</th>
                        <th>Quantity Fulfilled</th>
                        <th>Status</th>
                        <th>Created Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backOrders as $backOrder)
                        <tr>
                            <td>#{{ $backOrder->id }}</td>
                            <td>
                                <strong>Kit #{{ $backOrder->kit->id }}</strong>
                                <br><small class="text-muted">{{ $backOrder->kit->category_label }}</small>
                            </td>
                            <td>#{{ $backOrder->purchase_id }}</td>
                            <td><strong class="text-warning">{{ $backOrder->quantity_pending }}</strong></td>
                            <td>{{ $backOrder->quantity_fulfilled }}</td>
                            <td>
                                <span class="badge bg-warning">Pending</span>
                            </td>
                            <td>{{ $backOrder->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
