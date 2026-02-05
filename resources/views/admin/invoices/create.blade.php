@extends('layouts.admin')

@section('title', 'New Invoice')

@section('content')
    <div class="page-header">
        <h1 class="page-title">New Invoice</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
                <li class="breadcrumb-item active" aria-current="page">New Invoice</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Invoice</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.invoices.store') }}" id="invoice-form">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Invoice Number</label>
                        <p class="form-control-plaintext text-muted mb-0 small">Auto-generated when you save</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" readonly required>
                        @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                        @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ old('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="paid" {{ old('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ old('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($selectedUser)
                    <input type="hidden" name="use_product_quantities" value="1">
                    <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <p class="form-control-plaintext mb-0">{{ $selectedUser->name }} ({{ $selectedUser->email }})</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer Name</label>
                            <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $selectedUser->name) }}">
                            @error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer Email</label>
                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $selectedUser->email) }}">
                            @error('customer_email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Customer Phone</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $selectedUser->phone) }}">
                            @error('customer_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer Address</label>
                            <textarea name="customer_address" class="form-control" rows="2">{{ old('customer_address') }}</textarea>
                            @error('customer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <label class="form-label mb-0">Products — enter quantity needed</label>
                        <input type="search" id="product-search" class="form-control form-control-sm" placeholder="Search products..." style="max-width: 240px;" autocomplete="off">
                    </div>
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered" id="product-quantities-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="width:80px">Unit</th>
                                    <th style="width:120px" class="text-end">Unit Price</th>
                                    <th style="width:120px">Quantity</th>
                                    <th style="width:120px" class="text-end">Line Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                    @php $unitPrice = $product->getPriceForUser($selectedUser); @endphp
                                    <tr class="product-row" data-unit-price="{{ $unitPrice }}" data-search="{{ strtolower($product->name . ' ' . ($product->pack_size ?? '') . ' ' . ($product->item_code ?? '')) }}">
                                        <td>{{ $product->display_name }}</td>
                                        <td>{{ $product->pack_size ?? 'pcs' }}</td>
                                        <td class="text-end">{{ number_format($unitPrice, 2) }}</td>
                                        <td>
                                            <input type="number" name="product_quantities[{{ $product->id }}]" class="form-control form-control-sm product-qty" value="{{ old('product_quantities.'.$product->id, 0) }}" min="0" step="1" data-unit-price="{{ $unitPrice }}">
                                        </td>
                                        <td class="text-end"><span class="product-line-total">0.00</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><strong id="product-subtotal">0.00</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><label class="mb-0">Tax:</label></td>
                                    <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input" style="width:100px; margin-left: auto;"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><label class="mb-0">Discount:</label></td>
                                    <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input" style="width:100px; margin-left: auto;"></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong id="product-total">0.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @error('product_quantities')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @else
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Customer (User)</label>
                        <select name="user_id" class="form-select" id="user-select">
                            <option value="">— No user —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" id="customer-name">
                        @error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Email</label>
                        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}" id="customer-email">
                        @error('customer_email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Customer Phone</label>
                        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" id="customer-phone">
                        @error('customer_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Customer Address</label>
                        <textarea name="customer_address" class="form-control" rows="2" id="customer-address">{{ old('customer_address') }}</textarea>
                        @error('customer_address')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <label class="form-label">Items <span class="text-danger">*</span></label>
                <div class="table-responsive mb-2">
                    <table class="table table-bordered" id="invoice-items-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th style="width:100px">Quantity</th>
                                <th style="width:80px">Unit</th>
                                <th style="width:120px">Unit Price</th>
                                <th style="width:120px">Line Total</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="invoice-items-tbody">
                            <tr class="invoice-item-row">
                                <td><input type="text" name="items[0][item_name]" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="items[0][description]" class="form-control form-control-sm"></td>
                                <td><input type="number" name="items[0][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>
                                <td><input type="text" name="items[0][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                                <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="subtotal-display" readonly value="0.00"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><label class="mb-0">Tax:</label></td>
                                <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><label class="mb-0">Discount:</label></td>
                                <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="total-display" readonly value="0.00"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-row"><i class="fe fe-plus me-1"></i>Add row</button>
                @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @endif

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-primary">Create Invoice</button>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
    @if($selectedUser)
    <script>
    (function() {
        var table = document.getElementById('product-quantities-table');
        if (!table) return;
        function updateProductTotals() {
            var subtotal = 0;
            table.querySelectorAll('.product-row').forEach(function(row) {
                var qty = parseFloat(row.querySelector('.product-qty').value) || 0;
                var price = parseFloat(row.querySelector('.product-qty').getAttribute('data-unit-price')) || 0;
                var lineTotal = qty * price;
                row.querySelector('.product-line-total').textContent = lineTotal.toFixed(2);
                subtotal += lineTotal;
            });
            var tax = parseFloat(document.getElementById('tax-input').value) || 0;
            var discount = parseFloat(document.getElementById('discount-input').value) || 0;
            var total = subtotal + tax - discount;
            document.getElementById('product-subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('product-total').textContent = total.toFixed(2);
        }
        table.querySelectorAll('.product-qty').forEach(function(input) {
            input.addEventListener('input', updateProductTotals);
        });
        document.getElementById('tax-input').addEventListener('input', updateProductTotals);
        document.getElementById('discount-input').addEventListener('input', updateProductTotals);

        var searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var q = (this.value || '').trim().toLowerCase();
                table.querySelectorAll('.product-row').forEach(function(row) {
                    var text = (row.getAttribute('data-search') || '').toLowerCase();
                    row.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }

        updateProductTotals();
    })();
    </script>
    @else
    <script>
    (function() {
        var tbody = document.getElementById('invoice-items-tbody');
        if (!tbody) return;
        var rowIndex = 1;
        var template = document.createElement('template');
        template.innerHTML = `
            <tr class="invoice-item-row">
                <td><input type="text" name="items[__INDEX__][item_name]" class="form-control form-control-sm" required></td>
                <td><input type="text" name="items[__INDEX__][description]" class="form-control form-control-sm"></td>
                <td><input type="number" name="items[__INDEX__][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="1" required></td>
                <td><input type="text" name="items[__INDEX__][unit]" class="form-control form-control-sm" placeholder="pcs"></td>
                <td><input type="number" name="items[__INDEX__][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="0" required></td>
                <td><input type="text" class="form-control form-control-sm line-total" readonly value="0.00"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button></td>
            </tr>
        `;

        function updateTotals() {
            var subtotal = 0;
            document.querySelectorAll('.invoice-item-row').forEach(function(row) {
                var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                var price = parseFloat(row.querySelector('.item-price').value) || 0;
                var lineTotal = qty * price;
                row.querySelector('.line-total').value = lineTotal.toFixed(2);
                subtotal += lineTotal;
            });
            var tax = parseFloat(document.getElementById('tax-input').value) || 0;
            var discount = parseFloat(document.getElementById('discount-input').value) || 0;
            var total = subtotal + tax - discount;
            document.getElementById('subtotal-display').value = subtotal.toFixed(2);
            document.getElementById('total-display').value = total.toFixed(2);
        }

        document.getElementById('add-row').addEventListener('click', function() {
            var newRow = template.content.cloneNode(true);
            var html = newRow.querySelector('tr').outerHTML.replace(/__INDEX__/g, rowIndex++);
            tbody.insertAdjacentHTML('beforeend', html);
            tbody.querySelectorAll('.invoice-item-row').forEach(function(row) {
                row.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
                    input.removeEventListener('input', updateTotals);
                    input.addEventListener('input', updateTotals);
                });
            });
            updateTotals();
        });

        tbody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                if (tbody.querySelectorAll('.invoice-item-row').length > 1) {
                    e.target.closest('tr').remove();
                    updateTotals();
                } else {
                    alert('At least one item is required.');
                }
            }
        });

        tbody.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
            input.addEventListener('input', updateTotals);
        });
        document.getElementById('tax-input').addEventListener('input', updateTotals);
        document.getElementById('discount-input').addEventListener('input', updateTotals);

        // Auto-fill customer fields when user is selected
        document.getElementById('user-select').addEventListener('change', function() {
            var userId = this.value;
            if (userId) {
                var option = this.options[this.selectedIndex];
                var text = option.text;
                var match = text.match(/^(.+?)\s*\((.+?)\)$/);
                if (match) {
                    document.getElementById('customer-name').value = match[1].trim();
                    document.getElementById('customer-email').value = match[2].trim();
                }
            }
        });

        updateTotals();
    })();
    </script>
    @endif
    @endpush
@endsection
