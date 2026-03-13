@extends('layouts.admin')

@section('title', 'Order ' . ($order->invoice_number ?? $order->id) . ' – Dispatch')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Order {{ $order->invoice_number ?? 'ORD-' . $order->id }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.dispatch.orders.index') }}">Dispatch Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order</li>
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

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Items – Verify product, quantity, expiry</h3>
                    <div class="card-options">
                        @if($order->status === 'paid')
                            <span class="badge bg-info">Paid – Ready to pack</span>
                        @elseif($order->status === 'packed')
                            <span class="badge bg-primary">Packed</span>
                        @elseif($order->status === 'shipped')
                            <span class="badge bg-warning text-dark">Shipped</span>
                        @else
                            <span class="badge bg-success">Delivered</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item / Code</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                    <th>Batch / Expiry</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    @php $product = $productsByItemCode->get($item->item_code); @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $item->item_code }}</strong><br>
                                            <small class="text-muted">{{ $item->product_name }}</small>
                                        </td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                                        <td>
                                            @if($product)
                                                @if($product->batch_number)
                                                    <small>Batch: {{ $product->batch_number }}</small><br>
                                                @endif
                                                @if($product->expiry_date)
                                                    <small class="{{ $product->expiry_date->isPast() ? 'text-danger' : 'text-muted' }}">Exp: {{ $product->expiry_date->format('M Y') }}</small>
                                                @else
                                                    <small class="text-muted">—</small>
                                                @endif
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($order->status !== 'completed' && $order->status !== 'delivered')
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#exchangeModal{{ $item->id }}">
                                                    Exchange
                                                </button>

                                                {{-- Multi-Item Exchange Modal --}}
                                                <div class="modal fade" id="exchangeModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ route('admin.dispatch.orders.exchange-item', [$order, $item]) }}" class="exchange-form" data-target-total="{{ $item->line_total }}">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <div class="modal-title">
                                                                        <h5 class="mb-0">Exchange Item: {{ $item->item_code }}</h5>
                                                                        <small class="text-muted">Target Total: ₦{{ number_format($item->line_total, 2) }}</small>
                                                                    </div>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <div class="replacements-container">
                                                                        <div class="replacement-row row mb-2 align-items-end">
                                                                            <div class="col-md-7">
                                                                                <label class="form-label small">Product</label>
                                                                                <select name="replacement_items[0][product_id]" class="form-select select-product" required>
                                                                                    <option value="">-- Select Product --</option>
                                                                                    @foreach($allProducts as $p)
                                                                                        <option value="{{ $p->id }}" data-price="{{ $p->getPriceForUser($order->user) }}">
                                                                                            {{ $p->item_code }} - {{ $p->name }} (₦{{ number_format($p->getPriceForUser($order->user), 0) }})
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-3">
                                                                                <label class="form-label small">Qty</label>
                                                                                <input type="number" name="replacement_items[0][quantity]" class="form-control select-quantity" value="1" min="1" required>
                                                                            </div>
                                                                            <div class="col-md-2">
                                                                                <button type="button" class="btn btn-danger w-100 remove-row" style="display:none;"><i class="fe fe-trash"></i></button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="btn btn-sm btn-outline-primary add-row mt-2"><i class="fe fe-plus"></i> Add Another Product</button>
                                                                    
                                                                    <div class="mt-4 p-3 bg-light rounded">
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>New Total:</span>
                                                                            <span class="fw-bold"><span class="current-total">₦0.00</span></span>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between">
                                                                            <span>Remaining:</span>
                                                                            <span class="remaining-amount fw-bold text-success">₦{{ number_format($item->line_total, 2) }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="error-msg text-danger small mt-2 d-none">Total exceeds original price!</div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary submit-exchange">Confirm Exchange</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Customer & Shipping</h3>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $order->user?->name }}</strong></p>
                    <p class="mb-2 text-muted small">{{ $order->user?->email }}</p>
                    @if($order->shipping_address || $order->shipping_phone)
                        <p class="mb-1"><strong>Address</strong></p>
                        <p class="mb-2">{{ $order->shipping_address }}<br>
                            {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
                            Phone: {{ $order->shipping_phone }}
                        </p>
                    @endif
                    <hr>
                    <p class="mb-1"><strong>Subtotal</strong> <span class="float-end">₦{{ number_format($order->subtotal, 2) }}</span></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Shipping Cost</h3>
                </div>
                <div class="card-body">
                    <form id="shippingCostForm" method="POST" action="{{ route('admin.dispatch.orders.update-shipping-cost', $order) }}">
                        @csrf
                        <div class="mb-2">
                            <input type="text" id="shipping_cost_display" class="form-control form-control-sm text-end" value="{{ $order->shipping_cost > 0 ? number_format($order->shipping_cost, 2) : '' }}" placeholder="">
                            <input type="hidden" name="shipping_cost" id="shipping_cost_value" value="{{ $order->shipping_cost ?? 0 }}">
                            @error('shipping_cost')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Update Shipping Cost</button>
                    </form>
                    @if($order->shipping_cost)
                    <div class="mt-2 pt-2 border-top">
                        <p class="mb-0"><strong>Current Shipping:</strong> <span class="float-end">₦{{ number_format($order->shipping_cost, 2) }}</span></p>
                        <p class="mb-0 small text-muted"><strong>Grand Total:</strong> <span class="float-end fw-bold text-primary">₦{{ number_format($order->subtotal + $order->shipping_cost, 2) }}</span></p>
                    </div>
                    @endif
                </div>

                @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const displayInput = document.getElementById('shipping_cost_display');
                        const hiddenInput = document.getElementById('shipping_cost_value');

                        const formatNumber = (val) => {
                            if (!val) return '';
                            const parts = val.split('.');
                            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            return parts.join('.');
                        };

                        displayInput.addEventListener('input', function(e) {
                            let cursorPosition = this.selectionStart;
                            let originalLength = this.value.length;
                            
                            // Remove all non-numeric characters except the decimal point
                            let rawValue = this.value.replace(/[^0-9.]/g, '');
                            
                            // Prevent multiple decimal points
                            const decimalCount = (rawValue.match(/\./g) || []).length;
                            if (decimalCount > 1) {
                                rawValue = rawValue.slice(0, rawValue.lastIndexOf('.'));
                            }

                            hiddenInput.value = rawValue;
                            
                            // Apply formatting
                            const formattedValue = formatNumber(rawValue);
                            this.value = formattedValue;

                            // Adjust cursor position to handle added/removed commas
                            let newLength = this.value.length;
                            cursorPosition = cursorPosition + (newLength - originalLength);
                            this.setSelectionRange(cursorPosition, cursorPosition);
                        });

                        displayInput.addEventListener('blur', function() {
                            let val = parseFloat(hiddenInput.value) || 0;
                            this.value = val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            hiddenInput.value = val.toFixed(2);
                        });
                    });
                </script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const formatMoney = (val) => '₦' + parseFloat(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                        document.querySelectorAll('.exchange-form').forEach(form => {
                            const container = form.querySelector('.replacements-container');
                            const targetTotal = parseFloat(form.dataset.targetTotal);
                            const currentTotalSpan = form.querySelector('.current-total');
                            const remainingAmountSpan = form.querySelector('.remaining-amount');
                            const errorMsg = form.querySelector('.error-msg');
                            const submitBtn = form.querySelector('.submit-exchange');
                            let rowIndex = 1;

                            const updateTotals = () => {
                                let total = 0;
                                form.querySelectorAll('.replacement-row').forEach(row => {
                                    const productSelect = row.querySelector('.select-product');
                                    const qtyInput = row.querySelector('.select-quantity');
                                    const selectedOption = productSelect.options[productSelect.selectedIndex];
                                    
                                    if (selectedOption && selectedOption.value) {
                                        total += (parseFloat(selectedOption.dataset.price) || 0) * (parseInt(qtyInput.value) || 0);
                                    }
                                });

                                currentTotalSpan.textContent = formatMoney(total);
                                const remaining = targetTotal - total;
                                remainingAmountSpan.textContent = formatMoney(remaining);
                                
                                if (remaining < -0.01) {
                                    remainingAmountSpan.classList.remove('text-success');
                                    remainingAmountSpan.classList.add('text-danger');
                                    errorMsg.classList.remove('d-none');
                                    submitBtn.disabled = true;
                                } else {
                                    remainingAmountSpan.classList.remove('text-danger');
                                    remainingAmountSpan.classList.add('text-success');
                                    errorMsg.classList.add('d-none');
                                    submitBtn.disabled = total === 0;
                                }
                            };

                            form.querySelector('.add-row').addEventListener('click', () => {
                                const firstRow = container.querySelector('.replacement-row');
                                const newRow = firstRow.cloneNode(true);
                                
                                // Reset values and update indices
                                newRow.querySelectorAll('select, input').forEach(el => {
                                    el.name = el.name.replace('[0]', `[${rowIndex}]`);
                                    el.value = el.tagName === 'SELECT' ? '' : '1';
                                });
                                
                                // Show remove button
                                const removeBtn = newRow.querySelector('.remove-row');
                                removeBtn.style.display = 'block';
                                removeBtn.addEventListener('click', () => {
                                    newRow.remove();
                                    updateTotals();
                                });

                                container.appendChild(newRow);
                                rowIndex++;
                                
                                // Add listeners to new elements
                                newRow.querySelector('.select-product').addEventListener('change', updateTotals);
                                newRow.querySelector('.select-quantity').addEventListener('input', updateTotals);
                            });

                            // Listeners for initial row
                            form.querySelector('.select-product').addEventListener('change', updateTotals);
                            form.querySelector('.select-quantity').addEventListener('input', updateTotals);
                            
                            updateTotals();
                        });
                    });
                </script>
                @endpush
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tracking & Courier</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.dispatch.orders.update-tracking', $order) }}" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">Tracking number</label>
                            <input type="text" name="tracking_number" class="form-control form-control-sm" value="{{ $order->getRawOriginal('tracking_number') }}" placeholder="Courier tracking #">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Delivery courier / rider</label>
                            <input type="text" name="delivery_courier" class="form-control form-control-sm" value="{{ $order->delivery_courier }}" placeholder="Rider or courier name">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update status</h3>
                </div>
                <div class="card-body">
                    @if(in_array($order->status, ['paid', 'packed', 'shipped'], true))
                        <form method="POST" action="{{ route('admin.dispatch.orders.update-status', $order) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    @if(in_array($order->status, ['paid'], true))
                                        <option value="packed">Mark as Packed</option>
                                    @endif
                                    @if(in_array($order->status, ['paid', 'packed'], true))
                                        <option value="shipped">Mark as Shipped</option>
                                    @endif
                                    @if(in_array($order->status, ['paid', 'packed', 'shipped'], true))
                                        <option value="delivered">Mark as Delivered</option>
                                    @endif
                                </select>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">Update status</button>
                        </form>
                    @else
                        <p class="mb-0 text-muted small">Order delivered. No further status updates.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Print</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.dispatch.orders.invoice', $order) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary d-block mb-2"><i class="fe fe-file-text me-1"></i> Invoice</a>
                    <a href="{{ route('admin.dispatch.orders.delivery-note', $order) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary d-block mb-2"><i class="fe fe-file me-1"></i> Delivery note</a>
                    <a href="{{ route('admin.dispatch.orders.shipment-label', $order) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary d-block"><i class="fe fe-tag me-1"></i> Shipment label</a>
                </div>
            </div>
        </div>
    </div>
@endsection
