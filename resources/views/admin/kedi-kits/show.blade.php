@extends('layouts.admin')

@section('title', 'KEDI Kit Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">KEDI Kit #{{ $kediKit->id }}</h3>
                    <div class="card-options">
                        <a href="{{ route('admin.kedi-kits.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fe fe-arrow-left"></i> Back to List
                        </a>
                        <a href="{{ route('admin.kedi-kits.edit', $kediKit) }}" class="btn btn-warning btn-sm">
                            <i class="fe fe-edit"></i> Edit
                        </a>
                    </div>
                </div>
                <div class="card-body">
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

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Kit Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Category:</th>
                                    <td>
                                        <span class="badge bg-{{ $kediKit->category === 'english' ? 'primary' : 'info' }}">
                                            {{ $kediKit->category_label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Price:</th>
                                    <td>₦{{ number_format($kediKit->price, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Available Quantity:</th>
                                    <td>
                                        <strong class="text-{{ $kediKit->quantity > 0 ? 'success' : 'danger' }}">
                                            {{ $kediKit->quantity ?? 0 }}
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $kediKit->is_old ? 'warning' : 'success' }}">
                                            {{ $kediKit->is_old ? 'Old' : 'New' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Purchased By:</th>
                                    <td>
                                        @if($kediKit->purchasedBy)
                                            {{ $kediKit->purchasedBy->name }} ({{ $kediKit->purchasedBy->email }})
                                        @else
                                            <span class="text-muted">Not purchased yet</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td>{{ $kediKit->description ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $kediKit->createdBy->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $kediKit->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At:</th>
                                    <td>{{ $kediKit->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">KD Numbers ({{ $kediKit->items->count() }})</h5>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addKdNumbersModal">
                                <i class="fe fe-plus"></i> Add More KD Numbers
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>KD Number</th>
                                        <th>Status</th>
                                        <th>Who Bought It</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kediKit->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $item->kd_no }}</strong></td>
                                            <td>
                                                @if($item->purchased_by_user_id)
                                                    <span class="badge bg-success">Sold</span>
                                                @else
                                                    <span class="badge bg-{{ $item->is_old ? 'warning' : 'info' }}">
                                                        {{ $item->is_old ? 'Old' : 'New' }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item->purchasedBy)
                                                    {{ $item->purchasedBy->name }} ({{ $item->purchasedBy->email }})
                                                @else
                                                    <span class="text-muted">Not purchased yet</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No KD numbers in this kit.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h5>Who Bought It (Purchases)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Purchase ID</th>
                                        <th>Buyer</th>
                                        <th>Quantity</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Purchase Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($kediKit->purchases as $purchase)
                                        <tr>
                                            <td>#{{ $purchase->id }}</td>
                                            <td>{{ $purchase->buyer->name ?? 'N/A' }} ({{ $purchase->buyer->email ?? 'N/A' }})</td>
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
                                                @if(auth()->user()->id === $purchase->seller_user_id)
                                                    @if($purchase->status === 'pending')
                                                        <form action="{{ route('admin.kedi-kits.purchase.approve', $purchase) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" title="Approve and Give Kits">
                                                                <i class="fe fe-check"></i> Give Kits
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.kedi-kits.purchase.reject', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this purchase? Amount will be refunded.');">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Reject Purchase">
                                                                <i class="fe fe-x"></i> Reject
                                                            </button>
                                                        </form>
                                                    @elseif($purchase->status === 'approved' && $purchase->backOrders->where('status', 'pending')->count() > 0)
                                                        <a href="{{ route('admin.kedi-kits.purchase.seller') }}" class="btn btn-sm btn-primary" title="Fulfill Back Orders">
                                                            <i class="fe fe-package"></i> Fulfill Back Orders
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('admin.kedi-kits.purchase.show', $purchase) }}" class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fe fe-eye"></i> View
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.kedi-kits.purchase.show', $purchase) }}" class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fe fe-eye"></i> View
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No purchases yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($kediKit->backOrders->where('status', 'pending')->count() > 0)
                    <div class="mb-4">
                        <h5>Back Orders ({{ $kediKit->backOrders->where('status', 'pending')->count() }})</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Back Order ID</th>
                                        <th>Buyer</th>
                                        <th>Purchase ID</th>
                                        <th>Quantity Pending</th>
                                        <th>Quantity Fulfilled</th>
                                        <th>Available Stock</th>
                                        <th>Status</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kediKit->backOrders->where('status', 'pending') as $backOrder)
                                        <tr>
                                            <td>#{{ $backOrder->id }}</td>
                                            <td>{{ $backOrder->buyer->name ?? 'N/A' }} ({{ $backOrder->buyer->email ?? 'N/A' }})</td>
                                            <td>#{{ $backOrder->purchase_id }}</td>
                                            <td><strong class="text-warning">{{ $backOrder->quantity_pending }}</strong></td>
                                            <td>{{ $backOrder->quantity_fulfilled }}</td>
                                            <td>
                                                <strong class="text-{{ ($kediKit->quantity ?? 0) > 0 ? 'success' : 'danger' }}">
                                                    {{ $kediKit->quantity ?? 0 }}
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning">Pending</span>
                                            </td>
                                            <td>{{ $backOrder->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @if(auth()->user()->id === $backOrder->purchase->seller_user_id)
                                                    @if(($kediKit->quantity ?? 0) > 0)
                                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#fulfillBackOrderModal{{ $backOrder->id }}" title="Fulfill Back Order">
                                                            <i class="fe fe-check"></i> Give Kits
                                                        </button>
                                                    @else
                                                        <span class="text-muted">No stock</span>
                                                    @endif
                                                    <a href="{{ route('admin.kedi-kits.purchase.show', $backOrder->purchase_id) }}" class="btn btn-sm btn-info" title="View Purchase">
                                                        <i class="fe fe-eye"></i> View
                                                    </a>
                                                @else
                                                    <a href="{{ route('admin.kedi-kits.purchase.show', $backOrder->purchase_id) }}" class="btn btn-sm btn-info" title="View Purchase">
                                                        <i class="fe fe-eye"></i> View
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>

                                        <!-- Fulfill Back Order Modal -->
                                        @if(auth()->user()->id === $backOrder->purchase->seller_user_id)
                                        <div class="modal fade" id="fulfillBackOrderModal{{ $backOrder->id }}" tabindex="-1" aria-labelledby="fulfillBackOrderModalLabel{{ $backOrder->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="fulfillBackOrderModalLabel{{ $backOrder->id }}">Fulfill Back Order #{{ $backOrder->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('admin.kedi-kits.back-order.fulfill', $backOrder) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <p><strong>Buyer:</strong> {{ $backOrder->buyer->name }}</p>
                                                            <p><strong>Kit:</strong> #{{ $kediKit->id }} - {{ $kediKit->category_label }}</p>
                                                            <p><strong>Pending:</strong> {{ $backOrder->quantity_pending }}</p>
                                                            <p><strong>Available Stock:</strong> {{ $kediKit->quantity ?? 0 }}</p>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Quantity to Fulfill <span class="text-danger">*</span></label>
                                                                <input type="number" name="fulfill_quantity" class="form-control" value="{{ min($backOrder->quantity_pending, $kediKit->quantity ?? 0) }}" min="1" max="{{ min($backOrder->quantity_pending, $kediKit->quantity ?? 0) }}" required>
                                                                <small class="text-muted">Maximum: {{ min($backOrder->quantity_pending, $kediKit->quantity ?? 0) }}</small>
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
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('admin.kedi-kits.edit', $kediKit) }}" class="btn btn-warning">
                            <i class="fe fe-edit"></i> Edit Kit
                        </a>
                        <form action="{{ route('admin.kedi-kits.destroy', $kediKit) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this kit?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fe fe-trash"></i> Delete Kit
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add KD Numbers Modal -->
<div class="modal fade" id="addKdNumbersModal" tabindex="-1" aria-labelledby="addKdNumbersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addKdNumbersModalLabel">Add More KD Numbers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.kedi-kits.add-kd-numbers', $kediKit) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">KD Numbers <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-success" id="addKdRowModal">
                                <i class="fe fe-plus"></i> Add Row
                            </button>
                        </div>
                        <div id="kdNumbersContainerModal">
                            <!-- KD numbers will be added here dynamically -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-save"></i> Add KD Numbers
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('kdNumbersContainerModal');
    const addBtn = document.getElementById('addKdRowModal');
    let rowCount = 0;

    function addKdRowModal(kdNo = '', isOld = false, purchasedByUserId = '') {
        rowCount++;
        const row = document.createElement('div');
        row.className = 'row mb-2 kd-row-modal';
        row.dataset.index = rowCount;
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" name="kd_numbers[${rowCount}][kd_no]" class="form-control" placeholder="KD Number" value="${kdNo}" required>
            </div>
            <div class="col-md-3">
                <select name="kd_numbers[${rowCount}][is_old]" class="form-select">
                    <option value="0" ${!isOld ? 'selected' : ''}>New</option>
                    <option value="1" ${isOld ? 'selected' : ''}>Old</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="kd_numbers[${rowCount}][purchased_by_user_id]" class="form-select">
                    <option value="">Select User (Optional)</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-danger remove-row-modal">
                    <i class="fe fe-trash"></i>
                </button>
            </div>
        `;
        if (purchasedByUserId) {
            const select = row.querySelector('select[name*="[purchased_by_user_id]"]');
            if (select) select.value = purchasedByUserId;
        }
        container.appendChild(row);
    }

    addBtn.addEventListener('click', function() {
        addKdRowModal();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row-modal')) {
            e.target.closest('.kd-row-modal').remove();
        }
    });

    // Reset modal when opened
    document.getElementById('addKdNumbersModal').addEventListener('show.bs.modal', function() {
        container.innerHTML = '';
        rowCount = 0;
        addKdRowModal();
    });
});
</script>
@endpush
@endsection
