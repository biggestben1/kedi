@extends('layouts.admin')

@section('title', 'Edit Purchase')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Purchase</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.purchases.index') }}">Purchases</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Purchase Invoice {{ $purchase->purchase_invoice ?: '#' . $purchase->id }}</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.purchases.update', $purchase) }}" id="purchase-form">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select" required>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id', $purchase->supplier_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
                        @error('purchase_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Purchase Invoice #</label>
                        <input type="text" name="purchase_invoice" class="form-control" value="{{ old('purchase_invoice', $purchase->purchase_invoice') }}">
                        @error('purchase_invoice')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                        <select name="payment_status" class="form-select" required>
                            <option value="pending" {{ old('payment_status', $purchase->payment_status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="paid" {{ old('payment_status', $purchase->payment_status) === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="partial" {{ old('payment_status', $purchase->payment_status) === 'partial' ? 'selected' : '' }}>Partial</option>
                        </select>
                        @error('payment_status')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <label class="form-label">Items <span class="text-danger">*</span></label>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered" id="purchase-items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:100px">Quantity</th>
                                <th style="width:120px">Cost Price</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="purchase-items-tbody">
                            @foreach($purchase->items as $i => $item)
                                <tr class="purchase-item-row">
                                    <td>
                                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                        <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm product-select" required>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" data-cost="{{ $p->cost_price ?? 0 }}" {{ $item->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->item_code }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm" min="1" value="{{ old('items.'.$i.'.quantity', $item->quantity) }}" required></td>
                                    <td><input type="number" name="items[{{ $i }}][cost_price]" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('items.'.$i.'.cost_price', $item->cost_price) }}" required></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-row"><i class="fe fe-plus me-1"></i>Add row</button>
                @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <hr class="my-4">
                <button type="submit" class="btn btn-primary">Update Purchase</button>
                <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <template id="purchase-item-template">
        <tr class="purchase-item-row">
            <td>
                <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select" required>
                    <option value="">— Select product —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price ?? 0 }}">{{ $p->name }} ({{ $p->item_code }})</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[__INDEX__][quantity]" class="form-control form-control-sm" min="1" value="1" required></td>
            <td><input type="number" name="items[__INDEX__][cost_price]" class="form-control form-control-sm" step="0.01" min="0" value="0" required></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
        </tr>
    </template>

    @push('scripts')
    <script>
(function() {
    var tbody = document.getElementById('purchase-items-tbody');
    var template = document.getElementById('purchase-item-template');
    var addBtn = document.getElementById('add-row');
    var index = {{ $purchase->items->count() }};

    function addRow() {
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        tbody.insertAdjacentHTML('beforeend', html);
        index++;
    }

    addBtn && addBtn.addEventListener('click', addRow);

    tbody && tbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            var row = e.target.closest('tr');
            if (tbody.querySelectorAll('tr.purchase-item-row').length > 1) row.remove();
        }
    });

    tbody && tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            var opt = e.target.selectedOptions[0];
            var cost = opt ? parseFloat(opt.getAttribute('data-cost')) || 0 : 0;
            var row = e.target.closest('tr');
            var costInput = row.querySelector('input[name*="cost_price"]');
            if (costInput && cost > 0) costInput.value = cost;
        }
    });
})();
    </script>
    @endpush
@endsection
