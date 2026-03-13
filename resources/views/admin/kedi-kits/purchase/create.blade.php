@extends('layouts.admin')

@section('title', 'Purchase KEDI Kits')

@section('content')
<div class="page-header">
    <h1 class="page-title">Purchase KEDI Kits</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.kedi-kits.purchase.index') }}">Kit Purchases</a></li>
            <li class="breadcrumb-item active" aria-current="page">Purchase</li>
        </ol>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Purchase Kits from {{ $seller->name }}</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <strong>Wallet Balance:</strong> ₦{{ number_format($walletBalance, 2) }}
        </div>
        @if($availableKits->isEmpty())
            <div class="alert alert-info">
                <i class="fe fe-info me-2"></i>No kits available from {{ $seller->name }} at the moment.
            </div>
        @else
            <form action="{{ route('admin.kedi-kits.purchase.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="form-label">Select Kit <span class="text-danger">*</span></label>
                    <select name="kedi_kit_id" id="kit_select" class="form-select" required>
                        <option value="">Choose a kit...</option>
                        @foreach($availableKits as $kit)
                            <option value="{{ $kit->id }}" data-price="{{ $kit->price }}" data-quantity="{{ $kit->quantity }}" data-category="{{ $kit->category_label }}" data-description="{{ $kit->description ?? '' }}">
                                Kit #{{ $kit->id }} - {{ $kit->category_label }} (₦{{ number_format($kit->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('kedi_kit_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div id="kit_details" class="mb-4" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Kit Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Category:</strong> <span id="kit_category">-</span></p>
                                    <p class="mb-1"><strong>Price per Kit:</strong> ₦<span id="kit_price">0.00</span></p>
                                    <p class="mb-1"><strong>Available Quantity:</strong> <span id="kit_quantity_display">0</span></p>
                                    <p class="mb-1"><strong>Description:</strong> <span id="kit_description">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control" value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Price</label>
                        <input type="text" id="total_price_display" class="form-control" value="₦0.00" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-shopping-cart me-2"></i>Submit Purchase Request
                    </button>
                    <a href="{{ route('admin.kedi-kits.purchase.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const kitSelect = document.getElementById('kit_select');
    const kitDetails = document.getElementById('kit_details');
    const quantityInput = document.getElementById('quantity');
    const totalPriceDisplay = document.getElementById('total_price_display');

    function updateTotal() {
            const selectedOption = kitSelect.options[kitSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const price = parseFloat(selectedOption.dataset.price) || 0;
                const availableQty = parseInt(selectedOption.dataset.quantity) || 0;
                const quantity = parseInt(quantityInput.value) || 0;
                const total = price * quantity;
                totalPriceDisplay.value = '₦' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                // Show kit details
                document.getElementById('kit_category').textContent = selectedOption.dataset.category || '-';
                document.getElementById('kit_price').textContent = price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const qtyElement = document.getElementById('kit_quantity_display');
                qtyElement.textContent = availableQty;
                qtyElement.className = availableQty > 0 ? 'text-success' : 'text-danger';
                document.getElementById('kit_description').textContent = selectedOption.dataset.description || 'No description';
                kitDetails.style.display = 'block';
            } else {
                kitDetails.style.display = 'none';
                totalPriceDisplay.value = '₦0.00';
            }
    }

    kitSelect.addEventListener('change', updateTotal);
    quantityInput.addEventListener('input', updateTotal);
    
    // Initial update if there is a selected value
    if (kitSelect.value) {
        updateTotal();
    }
});
</script>
@endpush
@endsection
