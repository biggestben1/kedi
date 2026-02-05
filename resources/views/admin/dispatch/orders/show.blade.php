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
