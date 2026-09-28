@extends($layout)

@section('title', $pageTitle)

@php
    $oldItems = array_values(old('items', [['item_name' => '', 'quantity' => 1, 'unit_price' => '']]));
@endphp

@section('content')
    @if($layout === 'layouts.admin')
        <div class="page-header">
            <h1 class="page-title">{{ $pageTitle }}</h1>
        </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('collection-invoices.store') }}" id="collection-invoice-form">
        @csrf

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Customer &amp; collection center</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    Stock is <strong>not</strong> checked here. The invoice goes straight to the collection center you choose, and the items are removed from that center's stock when the customer collects.
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="customer_name">Customer name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required maxlength="255">
                        @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="customer_phone">Customer phone</label>
                        <input type="text" name="customer_phone" id="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" maxlength="50">
                        @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="collection_branch_id">Collection center <span class="text-danger">*</span></label>
                        <select name="collection_branch_id" id="collection_branch_id" class="form-select @error('collection_branch_id') is-invalid @enderror" required>
                            <option value="">— Select collection center —</option>
                            @foreach($collectionBranches as $branch)
                                <option value="{{ $branch->id }}" @selected((int) old('collection_branch_id') === (int) $branch->id)>
                                    {{ $branch->name }}@if($branch->phone) — {{ $branch->phone }}@endif
                                </option>
                            @endforeach
                        </select>
                        @error('collection_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Items</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-item-row"><i class="fe fe-plus me-1"></i>Add item</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="min-width: 260px;">Item</th>
                                <th style="width: 110px;">Qty</th>
                                <th style="width: 160px;">Unit price (₦)</th>
                                <th style="width: 150px;" class="text-end">Line total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="item-rows">
                            @foreach($oldItems as $i => $item)
                                <tr class="item-row">
                                    <td>
                                        <input type="text" name="items[{{ $i }}][item_name]" class="form-control item-name" list="product-options" value="{{ $item['item_name'] ?? '' }}" required maxlength="255" autocomplete="off" placeholder="Type or pick a product">
                                        <small class="text-warning item-warning d-none">Not in the product list — no stock will be removed for this item at collection.</small>
                                    </td>
                                    <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control item-qty" value="{{ $item['quantity'] ?? 1 }}" min="1" step="1" required></td>
                                    <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control item-price" value="{{ $item['unit_price'] ?? '' }}" min="0" step="0.01" required></td>
                                    <td class="text-end item-total">₦0.00</td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-item-row" title="Remove"><i class="fe fe-trash-2"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end" id="invoice-total">₦0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @error('items')<div class="text-danger small p-3">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" maxlength="2000">{{ old('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('collection-invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fe fe-send me-1"></i>Save &amp; send to collection center</button>
            </div>
        </div>
    </form>

    <datalist id="product-options">
        @foreach($productOptions as $option)
            <option value="{{ $option['name'] }}"></option>
        @endforeach
    </datalist>

    <script>
        (function () {
            const products = @json($productOptions);
            const priceByName = {};
            products.forEach(function (p) {
                [p.name].concat(p.aliases || []).forEach(function (label) {
                    priceByName[String(label).trim().toLowerCase()] = p.price;
                });
            });

            const tbody = document.getElementById('item-rows');
            const totalEl = document.getElementById('invoice-total');
            let nextIndex = tbody.querySelectorAll('.item-row').length;

            function money(n) {
                return '₦' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function refresh() {
                let total = 0;
                const rows = tbody.querySelectorAll('.item-row');
                rows.forEach(function (row) {
                    const name = row.querySelector('.item-name').value.trim().toLowerCase();
                    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                    const price = parseFloat(row.querySelector('.item-price').value) || 0;
                    const lineTotal = name === '' ? 0 : qty * price;
                    total += lineTotal;
                    row.querySelector('.item-total').textContent = money(lineTotal);
                    row.querySelector('.item-warning').classList.toggle('d-none', name === '' || name in priceByName);

                    // Blank rows are skipped on save, so only filled rows (or the only row) are required.
                    const required = name !== '' || rows.length === 1;
                    row.querySelectorAll('input').forEach(function (input) {
                        input.required = required;
                    });
                });
                totalEl.textContent = money(total);
            }

            function addRow(focus) {
                const template = tbody.querySelector('.item-row');
                const row = template.cloneNode(true);
                row.querySelectorAll('input').forEach(function (input) {
                    input.name = input.name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']');
                    input.value = input.classList.contains('item-qty') ? 1 : '';
                    input.classList.remove('is-invalid');
                });
                nextIndex++;
                tbody.appendChild(row);
                refresh();
                if (focus) {
                    row.querySelector('.item-name').focus();
                }
            }

            function ensureBlankRow() {
                const rows = tbody.querySelectorAll('.item-row');
                const last = rows[rows.length - 1];
                if (last.querySelector('.item-name').value.trim() !== '') {
                    addRow(false);
                }
            }

            function onNameChange(input) {
                const key = input.value.trim().toLowerCase();
                const priceInput = input.closest('.item-row').querySelector('.item-price');
                if (key in priceByName && priceInput.value === '') {
                    priceInput.value = priceByName[key];
                }
                if (key in priceByName) {
                    ensureBlankRow();
                }
                refresh();
            }

            tbody.addEventListener('input', function (e) {
                if (e.target.classList.contains('item-name')) {
                    onNameChange(e.target);
                } else {
                    refresh();
                }
            });

            tbody.addEventListener('change', function (e) {
                if (e.target.classList.contains('item-name')) {
                    ensureBlankRow();
                }
            });

            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('.remove-item-row');
                if (!btn) return;
                if (tbody.querySelectorAll('.item-row').length > 1) {
                    btn.closest('.item-row').remove();
                    ensureBlankRow();
                    refresh();
                }
            });

            document.getElementById('add-item-row').addEventListener('click', function () {
                addRow(true);
            });

            ensureBlankRow();
            refresh();
        })();
    </script>
@endsection
