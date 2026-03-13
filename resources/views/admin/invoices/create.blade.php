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

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Customer (User)</label>
                        <select name="user_id" class="form-select" id="user-select">
                            <option value="">— No user —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ old('user_id', $selectedUser?->id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $selectedUser?->name) }}" id="customer-name">
                        @error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Email</label>
                        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $selectedUser?->email) }}" id="customer-email">
                        @error('customer_email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Customer Phone</label>
                        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $selectedUser?->phone) }}" id="customer-phone">
                        @error('customer_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Customer Address</label>
                        <textarea name="customer_address" class="form-control" rows="2" id="customer-address">{{ old('customer_address') }}</textarea>
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
                                @if(!empty($branchStockByProduct))
                                <th style="width:80px" class="text-end">{{ ($serviceCenterOnly ?? false) ? 'Your Stock' : 'Branch Stock' }}</th>
                                @endif
                                <th style="width:120px" class="text-end">Unit Price</th>
                                <th style="width:120px">Quantity</th>
                                <th style="width:120px" class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                @php 
                                    $unitPrice = $selectedUser ? $product->getPriceForUser($selectedUser) : $product->price; 
                                    $branchStock = $branchStockByProduct[$product->id] ?? null; 
                                @endphp
                                <tr class="product-row" data-product-id="{{ $product->id }}" data-unit-price="{{ $unitPrice }}" data-search="{{ strtolower($product->name . ' ' . ($product->pack_size ?? '') . ' ' . ($product->item_code ?? '')) }}">
                                    <td>{{ $product->display_name }}</td>
                                    <td>{{ $product->pack_size ?? 'pcs' }}</td>
                                    @if(!empty($branchStockByProduct))
                                    <td class="text-end">{{ $branchStock ?? 0 }}</td>
                                    @endif
                                    <td class="text-end product-price">{{ number_format($unitPrice, 2) }}</td>
                                    <td>
                                        <input type="number" name="product_quantities[{{ $product->id }}]" class="form-control form-control-sm product-qty" value="{{ old('product_quantities.'.$product->id, 0) }}" min="0" step="1" data-unit-price="{{ $unitPrice }}" @if($branchStock !== null) max="{{ $branchStock }}" @endif>
                                    </td>
                                    <td class="text-end"><span class="product-line-total">0.00</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="{{ !empty($branchStockByProduct) ? 5 : 4 }}" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="text-end"><strong id="product-subtotal">0.00</strong></td>
                            </tr>
                            <tr>
                                <td colspan="{{ !empty($branchStockByProduct) ? 5 : 4 }}" class="text-end"><label class="mb-0">Tax:</label></td>
                                <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', 0) }}" id="tax-input" style="width:100px; margin-left: auto;"></td>
                            </tr>
                            <tr>
                                <td colspan="{{ !empty($branchStockByProduct) ? 5 : 4 }}" class="text-end"><label class="mb-0">Discount:</label></td>
                                <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', 0) }}" id="discount-input" style="width:100px; margin-left: auto;"></td>
                            </tr>
                            <tr>
                                <td colspan="{{ !empty($branchStockByProduct) ? 5 : 4 }}" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong id="product-total">0.00</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @error('product_quantities')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

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
    <script>
    (function() {
        var table = document.getElementById('product-quantities-table');
        if (!table) return;
        
        // Update prices when user is selected
        var userSelect = document.getElementById('user-select');
        if (userSelect) {
            userSelect.addEventListener('change', function() {
                var userId = this.value;
                if (userId) {
                    // Reload page with user_id to get correct prices
                    var url = new URL(window.location.href);
                    url.searchParams.set('user_id', userId);
                    window.location.href = url.toString();
                } else {
                    // Remove user_id and reload
                    var url = new URL(window.location.href);
                    url.searchParams.delete('user_id');
                    window.location.href = url.toString();
                }
            });
        }
        
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

        // Auto-fill customer fields when user is selected
        if (userSelect) {
            userSelect.addEventListener('change', function() {
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
        }

        updateProductTotals();
    })();
    </script>
    @endpush
@endsection
