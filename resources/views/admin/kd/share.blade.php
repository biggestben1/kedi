@extends('layouts.admin')

@section('title', 'Share Products to Friend')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Share Products to Friend</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.index') }}">Borrow</a></li>
                <li class="breadcrumb-item active" aria-current="page">Share Products</li>
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
            <strong>Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fe fe-share-2 me-2"></i>Share Products to Friend
                @if($kd)
                    <small class="text-muted">(KD: {{ $kd->kd_no }})</small>
                @endif
            </h3>
            <button type="button" class="btn btn-primary" id="shareSelectedBtn" disabled>
                <i class="fe fe-share-2 me-1"></i>Share Selected
            </button>
        </div>
        <div class="card-body">
            <h5 class="mb-3">
                Select Products from Your Orders
                @if($kd)
                    <small class="text-muted">(Showing orders for KD: <strong>{{ $kd->kd_no }}</strong>)</small>
                @endif
            </h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px">
                                <input type="checkbox" id="selectAll" title="Select All">
                            </th>
                            <th>Order</th>
                            <th>Product</th>
                            <th class="text-end">You Have</th>
                            <th class="text-end">Price</th>
                            <th class="text-end" style="width:120px">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasShareable = false; @endphp
                        @foreach($myOrders ?? [] as $order)
                            @foreach($order->items as $item)
                                @if($item->quantity > 0)
                                @php $hasShareable = true; @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="product-checkbox" data-item-id="{{ $item->id }}" data-max-qty="{{ $item->quantity }}" data-product-name="{{ $item->product_name }}">
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->invoice_number ?? 'ORD-' . $order->id }}</a>
                                        <br><small class="text-muted">{{ $order->created_at->format('Y-m-d') }}</small>
                                    </td>
                                    <td>{{ $item->product_name }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">₦{{ number_format($item->line_total, 0) }}</td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm share-quantity" data-item-id="{{ $item->id }}" min="1" max="{{ $item->quantity }}" value="1" style="width:80px" disabled>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        @endforeach
                        @if(!$hasShareable)
                            <tr><td colspan="6" class="text-center text-muted py-4">No products to share. <a href="{{ url('/') }}">Shop</a> first to place orders.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.kd.index') }}" class="btn btn-outline-secondary"><i class="fe fe-arrow-left me-1"></i>Back to Borrow</a>
        </div>
    </div>

    {{-- Share Modal --}}
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.kd.share') }}" id="shareForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Share Selected Products</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="selectedProductsList" class="mb-3"></div>
                        <div class="mb-3">
                            <label class="form-label">Friend's KD NO</label>
                            <input type="text" name="friend_kd_no" class="form-control" placeholder="e.g. KD-5-0001" value="{{ $kd->kd_no ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Friend's Name</label>
                            <input type="text" name="friend_name" class="form-control" placeholder="e.g. John Doe" value="{{ $kd->customer_name ?? '' }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fe fe-share-2 me-1"></i>Share</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const quantityInputs = document.querySelectorAll('.share-quantity');
            const shareBtn = document.getElementById('shareSelectedBtn');
            const shareForm = document.getElementById('shareForm');
            const selectedProductsList = document.getElementById('selectedProductsList');

            // Select all checkbox
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                        const qtyInput = document.querySelector(`.share-quantity[data-item-id="${cb.dataset.itemId}"]`);
                        if (qtyInput) {
                            qtyInput.disabled = !this.checked;
                        }
                    });
                    updateShareButton();
                });
            }

            // Individual checkbox change
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const qtyInput = document.querySelector(`.share-quantity[data-item-id="${this.dataset.itemId}"]`);
                    if (qtyInput) {
                        qtyInput.disabled = !this.checked;
                    }
                    updateShareButton();
                    updateSelectAll();
                });
            });

            // Quantity input change
            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const maxQty = parseInt(this.getAttribute('max'));
                    const value = parseInt(this.value) || 1;
                    if (value > maxQty) {
                        this.value = maxQty;
                    } else if (value < 1) {
                        this.value = 1;
                    }
                });
            });

            function updateSelectAll() {
                if (selectAll && checkboxes.length > 0) {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            }

            function updateShareButton() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);
                shareBtn.disabled = selected.length === 0;
            }

            // Share button click
            shareBtn.addEventListener('click', function() {
                const selected = Array.from(checkboxes).filter(cb => cb.checked);
                if (selected.length === 0) return;

                // Build products list
                let productsHtml = '<div class="alert alert-info"><strong>Selected Products:</strong><ul class="mb-0 mt-2">';
                selected.forEach(cb => {
                    const qtyInput = document.querySelector(`.share-quantity[data-item-id="${cb.dataset.itemId}"]`);
                    const qty = qtyInput ? qtyInput.value : 1;
                    productsHtml += `<li>${cb.dataset.productName} × ${qty}</li>`;
                });
                productsHtml += '</ul></div>';
                selectedProductsList.innerHTML = productsHtml;

                // Clear form inputs
                shareForm.querySelector('input[name="friend_kd_no"]').value = '';
                shareForm.querySelector('input[name="friend_name"]').value = '';

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('shareModal'));
                modal.show();
            });

            // Form submit - handle multiple items
            shareForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const selected = Array.from(checkboxes).filter(cb => cb.checked);
                if (selected.length === 0) return;

                const friendKdNo = this.querySelector('input[name="friend_kd_no"]').value.trim();
                const friendName = this.querySelector('input[name="friend_name"]').value.trim();

                if (!friendKdNo || !friendName) {
                    alert('Please enter friend\'s KD NO and Name');
                    return;
                }

                // Remove any previously appended hidden inputs (prevents duplicates)
                this.querySelectorAll('input[name="order_item_id[]"], input[name="quantity[]"]').forEach((el) => el.remove());

                // Add all selected items to form
                selected.forEach(cb => {
                    const qtyInput = document.querySelector(`.share-quantity[data-item-id="${cb.dataset.itemId}"]`);
                    const qty = qtyInput ? parseInt(qtyInput.value) : 1;
                    
                    const itemIdInput = document.createElement('input');
                    itemIdInput.type = 'hidden';
                    itemIdInput.name = 'order_item_id[]';
                    itemIdInput.value = cb.dataset.itemId;
                    this.appendChild(itemIdInput);
                    
                    const qtyInputHidden = document.createElement('input');
                    qtyInputHidden.type = 'hidden';
                    qtyInputHidden.name = 'quantity[]';
                    qtyInputHidden.value = qty;
                    this.appendChild(qtyInputHidden);
                });

                // Submit form
                this.submit();
            });
        });
    </script>
@endsection
