@extends('layouts.admin')

@section('title', 'Order ' . ($order->invoice_number ?? 'ORD-' . $order->id) . ' – View')

@section('content')
<div class="page-header">
    <h1 class="page-title">Order {{ $order->invoice_number ?? 'ORD-' . $order->id }}</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.dispatch.orders.index') }}">Dispatch Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">View</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4 p-md-5">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-start mb-4 pb-4 border-bottom">
                    <div>
                        <h2 class="mb-1 fw-bold" style="font-size:1.8rem; letter-spacing:-.5px;">INVOICE</h2>
                        <p class="mb-0 text-muted">Order #: <strong class="text-dark">{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong></p>
                        <p class="mb-0 text-muted">Date: {{ $order->created_at->format('d M Y, H:i') }}</p>
                        @if($order->kd_id)
                        <p class="mb-0 text-muted">KD No: <strong class="text-dark">{{ $order->kd_id }}</strong></p>
                        @endif
                    </div>
                    <div class="text-end">
                        <p class="mb-0 fw-bold fs-5">{{ config('app.name') }}</p>
                        {{-- Status badge --}}
                        <div class="mt-2">
                            @if($order->status === 'paid')
                                <span class="badge bg-info fs-6 px-3 py-2">Paid</span>
                            @elseif($order->status === 'packed')
                                <span class="badge bg-primary fs-6 px-3 py-2">Packed</span>
                            @elseif($order->status === 'shipped')
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2">Shipped</span>
                            @elseif($order->status === 'completed' || $order->status === 'delivered')
                                <span class="badge bg-success fs-6 px-3 py-2">Completed</span>
                            @else
                                <span class="badge bg-secondary fs-6 px-3 py-2">{{ ucfirst($order->status) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Customer / Shipping Info --}}
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem; letter-spacing:.08em;">Bill To</h6>
                        <p class="mb-0 fw-semibold">{{ $order->customer_name ?? $order->user?->name ?? '—' }}</p>
                        @if($order->user?->email)
                        <p class="mb-0 text-muted small">{{ $order->user->email }}</p>
                        @endif
                        @if($order->user?->kd_id ?? $order->kd_id)
                        <p class="mb-0 text-muted small">KD No: {{ $order->kd_id ?? $order->user?->kd_id }}</p>
                        @endif
                        @if($order->shipping_phone)
                        <p class="mb-0 text-muted small">Phone: {{ $order->shipping_phone }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem; letter-spacing:.08em;">Ship To</h6>
                        @if($order->shipping_address)
                        <p class="mb-0">{{ $order->shipping_address }}</p>
                        @endif
                        <p class="mb-0">
                            {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }}
                            {{ $order->shipping_postal_code ?? '' }}
                        </p>
                        @if($order->shipping_phone)
                        <p class="mb-0 text-muted small">Phone: {{ $order->shipping_phone }}</p>
                        @endif
                        @if($order->delivery_courier)
                        <p class="mb-0 text-muted small">Courier: {{ $order->delivery_courier }}</p>
                        @endif
                        @if($order->getRawOriginal('tracking_number'))
                        <p class="mb-0 text-muted small">Tracking: {{ $order->getRawOriginal('tracking_number') }}</p>
                        @endif
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50%">Product / Item</th>
                                <th class="text-center">Code</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $item->product_name }}</span>
                                </td>
                                <td class="text-center text-muted small">{{ $item->item_code ?? '—' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No items on this order.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                <td class="text-end fw-bold">₦{{ number_format($order->subtotal, 2) }}</td>
                            </tr>
                            @if($order->shipping_cost > 0)
                            <tr>
                                <td colspan="4" class="text-end text-muted">Shipping Cost</td>
                                <td class="text-end text-muted">₦{{ number_format($order->shipping_cost, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="table-primary text-primary">
                                <td colspan="4" class="text-end fw-bold fs-5">Total</td>
                                <td class="text-end fw-bold fs-5">₦{{ number_format($order->subtotal + $order->shipping_cost, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Payment / Delivery Info --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem; letter-spacing:.08em;">Payment</h6>
                        <p class="mb-0">
                            @if($order->payment_method === 'wallet')
                                <span class="badge bg-info">Wallet</span>
                            @elseif($order->payment_method === 'dpbv')
                                <span class="badge bg-purple" style="background:#6f42c1;">DPBV</span>
                            @else
                                <span class="badge bg-secondary">Pay on Delivery</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem; letter-spacing:.08em;">Delivery</h6>
                        <p class="mb-0">
                            @if($order->delivery_type === 'walk_in')
                                Walk-in
                            @else
                                Ship
                            @endif
                            @if($order->delivered_at)
                                &mdash; Delivered on {{ $order->delivered_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 flex-wrap border-top pt-4">
                    <a href="{{ route('admin.dispatch.orders.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-1"></i> Back to Orders
                    </a>
                    <a href="{{ route('admin.dispatch.orders.invoice', $order) }}" target="_blank" class="btn btn-outline-primary">
                        <i class="fe fe-printer me-1"></i> Print Invoice
                    </a>
                    <a href="{{ route('admin.dispatch.orders.delivery-note', $order) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="fe fe-file me-1"></i> Delivery Note
                    </a>
                    <a href="{{ route('admin.dispatch.orders.show', $order) }}" class="btn btn-primary ms-auto">
                        <i class="fe fe-settings me-1"></i> Process Order
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
