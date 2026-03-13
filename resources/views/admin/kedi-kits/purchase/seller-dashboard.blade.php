@extends('layouts.admin')

@section('title', 'Kit Purchase Management')

@section('content')
<div class="page-header">
    <h1 class="page-title">Kit Purchase Management</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kit Purchase Management</li>
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

@if($pendingPurchases->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-warning">
        <h3 class="card-title text-white">Pending Approvals ({{ $pendingPurchases->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Purchase ID</th>
                        <th>Kit</th>
                        <th>Buyer</th>
                        <th>Quantity</th>
                        <th>Available Stock</th>
                        <th>Total Price</th>
                        <th>Purchase Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingPurchases as $purchase)
                        <tr>
                            <td>#{{ $purchase->id }}</td>
                            <td>
                                <strong>Kit #{{ $purchase->kit->id }}</strong>
                                <br><small class="text-muted">{{ $purchase->kit->category_label }}</small>
                            </td>
                            <td>{{ $purchase->buyer->name }} ({{ $purchase->buyer->email }})</td>
                            <td>{{ $purchase->quantity }}</td>
                            <td>
                                <strong class="text-{{ ($purchase->kit->quantity ?? 0) > 0 ? 'success' : 'danger' }}">
                                    {{ $purchase->kit->quantity ?? 0 }}
                                </strong>
                            </td>
                            <td>₦{{ number_format($purchase->total_price, 2) }}</td>
                            <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.kedi-kits.purchase.show', $purchase) }}" class="btn btn-sm btn-info">
                                    <i class="fe fe-eye"></i> View
                                </a>
                                <form action="{{ route('admin.kedi-kits.purchase.approve', $purchase) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fe fe-check"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('admin.kedi-kits.purchase.reject', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this purchase? Amount will be refunded.');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fe fe-x"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($backOrders->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-danger">
        <h3 class="card-title text-white">Back Orders - What You Haven't Given Yet ({{ $backOrders->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Back Order ID</th>
                        <th>Kit</th>
                        <th>Buyer</th>
                        <th>Purchase ID</th>
                        <th>Quantity Pending</th>
                        <th>Quantity Fulfilled</th>
                        <th>Available Stock</th>
                        <th>Created Date</th>
                        <th>Actions</th>
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
                            <td>{{ $backOrder->buyer->name }} ({{ $backOrder->buyer->email }})</td>
                            <td>#{{ $backOrder->purchase_id }}</td>
                            <td><strong class="text-warning">{{ $backOrder->quantity_pending }}</strong></td>
                            <td>{{ $backOrder->quantity_fulfilled }}</td>
                            <td>
                                <strong class="text-{{ ($backOrder->kit->quantity ?? 0) > 0 ? 'success' : 'danger' }}">
                                    {{ $backOrder->kit->quantity ?? 0 }}
                                </strong>
                            </td>
                            <td>{{ $backOrder->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if(($backOrder->kit->quantity ?? 0) > 0)
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#fulfillModal{{ $backOrder->id }}">
                                    <i class="fe fe-check"></i> Fulfill
                                </button>
                                @else
                                <span class="text-muted">No stock</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Fulfill Modal -->
                        <div class="modal fade" id="fulfillModal{{ $backOrder->id }}" tabindex="-1" aria-labelledby="fulfillModalLabel{{ $backOrder->id }}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="fulfillModalLabel{{ $backOrder->id }}">Fulfill Back Order #{{ $backOrder->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="{{ route('admin.kedi-kits.back-order.fulfill', $backOrder) }}" method="POST">
                                        @csrf
                                        <div class="modal-body">
                                            <p><strong>Buyer:</strong> {{ $backOrder->buyer->name }}</p>
                                            <p><strong>Kit:</strong> #{{ $backOrder->kit->id }} - {{ $backOrder->kit->category_label }}</p>
                                            <p><strong>Pending:</strong> {{ $backOrder->quantity_pending }}</p>
                                            <p><strong>Available Stock:</strong> {{ $backOrder->kit->quantity ?? 0 }}</p>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Quantity to Fulfill <span class="text-danger">*</span></label>
                                                <input type="number" name="fulfill_quantity" class="form-control" value="{{ min($backOrder->quantity_pending, $backOrder->kit->quantity ?? 0) }}" min="1" max="{{ min($backOrder->quantity_pending, $backOrder->kit->quantity ?? 0) }}" required>
                                                <small class="text-muted">Maximum: {{ min($backOrder->quantity_pending, $backOrder->kit->quantity ?? 0) }}</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fe fe-check me-2"></i>Fulfill
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Purchases</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Purchase ID</th>
                        <th>Kit</th>
                        <th>Buyer</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Purchase Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allPurchases as $purchase)
                        <tr>
                            <td>#{{ $purchase->id }}</td>
                            <td>
                                <strong>Kit #{{ $purchase->kit->id }}</strong>
                                <br><small class="text-muted">{{ $purchase->kit->category_label }}</small>
                            </td>
                            <td>{{ $purchase->buyer->name }} ({{ $purchase->buyer->email }})</td>
                            <td>{{ $purchase->quantity }}</td>
                            <td>₦{{ number_format($purchase->total_price, 2) }}</td>
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
                            <td colspan="8" class="text-center">No purchases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $allPurchases->links() }}
    </div>
</div>
@endsection
