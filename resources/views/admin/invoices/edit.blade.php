@extends('layouts.admin')

@section('title', 'Edit Invoice')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Invoice</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
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
        <div class="card-header"><h3 class="card-title">Invoice {{ $invoice->invoice_number }}</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" id="invoice-form">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $invoice->invoice_number) }}" required maxlength="100">
                        @error('invoice_number')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                        @error('invoice_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                        @error('due_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="sent" {{ old('status', $invoice->status) === 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            <option value="cancelled" {{ old('status', $invoice->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                                <option value="{{ $u->id }}" {{ old('user_id', $invoice->user_id) == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $invoice->customer_name) }}" id="customer-name">
                        @error('customer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Email</label>
                        <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $invoice->customer_email) }}" id="customer-email">
                        @error('customer_email')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Customer Phone</label>
                        <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $invoice->customer_phone) }}" id="customer-phone">
                        @error('customer_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Customer Address</label>
                        <textarea name="customer_address" class="form-control" rows="2" id="customer-address">{{ old('customer_address', $invoice->customer_address) }}</textarea>
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
                            @foreach($invoice->items as $index => $item)
                            <tr class="invoice-item-row">
                                <td><input type="text" name="items[{{ $index }}][item_name]" class="form-control form-control-sm" value="{{ old("items.{$index}.item_name", $item->item_name) }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][description]" class="form-control form-control-sm" value="{{ old("items.{$index}.description", $item->description) }}"></td>
                                <td><input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-sm item-qty" step="0.01" min="0.01" value="{{ old("items.{$index}.quantity", $item->quantity) }}" required></td>
                                <td><input type="text" name="items[{{ $index }}][unit]" class="form-control form-control-sm" value="{{ old("items.{$index}.unit", $item->unit) }}" placeholder="pcs"></td>
                                <td><input type="number" name="items[{{ $index }}][unit_price]" class="form-control form-control-sm item-price" step="0.01" min="0" value="{{ old("items.{$index}.unit_price", $item->unit_price) }}" required></td>
                                <td><input type="text" class="form-control form-control-sm line-total" readonly value="{{ number_format($item->line_total, 2) }}"></td>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove row">×</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="subtotal-display" readonly value="{{ number_format($invoice->subtotal, 2) }}"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><label class="mb-0">Tax:</label></td>
                                <td><input type="number" name="tax" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('tax', $invoice->tax) }}" id="tax-input"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><label class="mb-0">Discount:</label></td>
                                <td><input type="number" name="discount" class="form-control form-control-sm" step="0.01" min="0" value="{{ old('discount', $invoice->discount) }}" id="discount-input"></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                <td><input type="text" class="form-control form-control-sm" id="total-display" readonly value="{{ number_format($invoice->total, 2) }}"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-row"><i class="fe fe-plus me-1"></i>Add row</button>
                @error('items')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                    @error('notes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <hr class="my-4">
                <button type="submit" class="btn btn-primary">Update Invoice</button>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    (function() {
        var tbody = document.getElementById('invoice-items-tbody');
        var rowIndex = {{ $invoice->items->count() }};
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
    @endpush
@endsection
