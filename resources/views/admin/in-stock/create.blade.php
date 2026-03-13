@extends('layouts.admin')

@section('title', 'New Factory Invoice')

@section('content')
    <div class="page-header">
        <h1 class="page-title">New Factory Invoice</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.in-stock.index') }}">In Stock</a></li>
                <li class="breadcrumb-item active" aria-current="page">New</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Factory Invoice (In Stock)</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.in-stock.store') }}" id="in-stock-form">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Invoice Number</label>
                        <input type="text" class="form-control bg-light" value="{{ $nextInvoiceNumber ?? 'FI-000001' }}" readonly disabled>
                        <small class="text-muted">Auto-generated on save</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Factory Name</label>
                        <input type="text" name="factory_name" class="form-control" value="{{ old('factory_name') }}" placeholder="Optional">
                        @error('factory_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                        @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <label class="form-label">Products <span class="text-danger">*</span> – set status per product</label>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="width:100px">Quantity</th>
                                <th style="width:150px">Status <span class="text-muted small">(per product)</span></th>
                                <th style="width:120px">Cost Price</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                            <tr class="item-row">
                                <td>
                                    <select name="items[0][product_id]" class="form-select form-select-sm product-select" required>
                                        <option value="">— Select product —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-cost="{{ $p->cost_price ?? 0 }}">{{ $p->display_name ?? $p->name }} ({{ $p->item_code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm" min="0.01" step="0.01" value="1" required></td>
                                <td>
                                    <select name="items[0][status]" class="form-select form-select-sm" required>
                                        @foreach($statusOptions as $val => $label)
                                            <option value="{{ $val }}" {{ $val === 'achievement' ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][cost_price]" class="form-control form-control-sm" step="0.01" min="0" value="0"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">×</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-row"><i class="fe fe-plus me-1"></i>Add product</button>
                @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <hr class="my-4">
                <button type="submit" class="btn btn-primary">Save Factory Invoice</button>
                <a href="{{ route('admin.in-stock.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <template id="item-template">
        <tr class="item-row">
            <td>
                <select name="items[__INDEX__][product_id]" class="form-select form-select-sm product-select" required>
                    <option value="">— Select product —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-cost="{{ $p->cost_price ?? 0 }}">{{ $p->display_name ?? $p->name }} ({{ $p->item_code }})</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[__INDEX__][quantity]" class="form-control form-control-sm" min="0.01" step="0.01" value="1" required></td>
            <td>
                <select name="items[__INDEX__][status]" class="form-select form-select-sm" required>
                    @foreach($statusOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[__INDEX__][cost_price]" class="form-control form-control-sm" step="0.01" min="0" value="0"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">×</button></td>
        </tr>
    </template>

    @push('scripts')
    <script>
(function() {
    var tbody = document.getElementById('items-tbody');
    var template = document.getElementById('item-template');
    var index = 1;

    document.getElementById('add-row').addEventListener('click', function() {
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        tbody.insertAdjacentHTML('beforeend', html);
        index++;
    });

    tbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row') && tbody.querySelectorAll('tr.item-row').length > 1) {
            e.target.closest('tr').remove();
        }
    });

    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('product-select')) {
            var opt = e.target.selectedOptions[0];
            var cost = opt ? parseFloat(opt.getAttribute('data-cost')) || 0 : 0;
            var costInput = e.target.closest('tr').querySelector('input[name*="cost_price"]');
            if (costInput && cost > 0) costInput.value = cost;
        }
    });
})();
    </script>
    @endpush
@endsection
