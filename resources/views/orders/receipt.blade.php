<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Order Receipt – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <style>
        .receipt-paper { max-width: 800px; margin: 0 auto; padding: 24px; }
        .receipt-page { page-break-after: always; }
        .receipt-page:last-child { page-break-after: auto; }
        @media print { .no-print { display: none !important; } .receipt-paper { padding: 0; } }
    </style>
</head>
<body class="app">
    <div class="page">
        <div class="page-main">
            <div class="app-header header sticky no-print">
                <div class="container-fluid main-container">
                    <div class="d-flex align-items-center">
                        <a class="header-brand1" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img" alt="{{ config('app.name') }}" style="max-height: 36px;">
                        </a>
                        <div class="ms-auto">
                            <a href="{{ route('orders.receipt.pdf', $order) }}" class="btn btn-sm btn-primary" target="_blank"><i class="fe fe-download me-1"></i>Download PDF</a>
                            <a href="{{ route('orders.receipt.pdf', $order) }}" class="btn btn-sm btn-outline-secondary" onclick="window.print(); return false;"><i class="fe fe-printer me-1"></i>Print</a>
                            <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">My Orders</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @php
                            $placedOrdersFull = $placedOrdersFull ?? collect();
                            $appName = config('app.name');
                        @endphp
                        @if($placedOrdersFull->isNotEmpty())
                            <p class="no-print mb-3"><strong>{{ $placedOrdersFull->count() }} orders placed.</strong> Scroll down to see each receipt. <a href="{{ route('orders.index') }}">View all orders</a>.</p>
                        @endif

                        @if($placedOrdersFull->isNotEmpty())
                            @foreach($placedOrdersFull as $idx => $ord)
                            <div class="card shadow receipt-paper receipt-page">
                                <div class="card-body">
                                    <div class="no-print mb-2 d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">Receipt {{ $idx + 1 }} of {{ $placedOrdersFull->count() }}</span>
                                        <a href="{{ route('orders.receipt.pdf', $ord) }}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fe fe-download me-1"></i>Download PDF</a>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                                        <div>
                                            <h2 class="mb-0">Order Receipt</h2>
                                            <p class="text-muted mb-0"><strong>Order #:</strong> {{ $ord->invoice_number ?? 'ORD-' . $ord->id }}</p>
                                            <p class="text-muted mb-0"><strong>Date:</strong> {{ $ord->created_at->format('M d, Y H:i') }}</p>
                                            <p class="text-muted mb-0"><strong>Payment:</strong> {{ $ord->payment_method === 'wallet' ? 'Wallet' : 'Pay on Delivery' }}</p>
                                            <p class="text-muted mb-0 mt-2"><strong>KD NO:</strong> {{ $ord->kd_id ?? '—' }}</p>
                                            <p class="text-muted mb-0"><strong>Customer Name:</strong> {{ $ord->customer_name ?? '—' }}</p>
                                        </div>
                                        <div class="text-end">
                                            <strong>{{ $appName }}</strong>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Customer (Account)</strong></p>
                                            <p class="mb-0">{{ $ord->user?->name }}<br>{{ $ord->user?->email }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Delivery</strong></p>
                                            <p class="mb-0">
                                                @if($ord->shipping_address){{ $ord->shipping_address }}<br>@endif
                                                {{ $ord->shipping_city }}{{ $ord->shipping_state ? ', ' . $ord->shipping_state : '' }} {{ $ord->shipping_postal_code ?? '' }}<br>
                                                @if($ord->shipping_phone){{ $ord->shipping_phone }}@endif
                                            </p>
                                        </div>
                                    </div>

                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Unit price</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($ord->items as $item)
                                            <tr>
                                                <td>{{ $item->product_name }} ({{ $item->item_code }})</td>
                                                <td class="text-end">{{ $item->quantity }}</td>
                                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                                <td class="text-end"><strong>₦{{ number_format($ord->subtotal, 2) }}</strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <p class="text-muted small mb-0 mt-3">Thank you for your order.</p>
                                </div>
                            </div>
                            @endforeach
                        @else
                        <div class="card shadow receipt-paper">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                                    <div>
                                        <h2 class="mb-0">Order Receipt</h2>
                                        <p class="text-muted mb-0"><strong>Order #:</strong> {{ $order->invoice_number ?? 'ORD-' . $order->id }}</p>
                                        <p class="text-muted mb-0"><strong>Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                                        <p class="text-muted mb-0"><strong>Payment:</strong> {{ $order->payment_method === 'wallet' ? 'Wallet' : 'Pay on Delivery' }}</p>
                                        <p class="text-muted mb-0 mt-2"><strong>KD NO:</strong> {{ $order->kd_id ?? '—' }}</p>
                                        <p class="text-muted mb-0"><strong>Customer Name:</strong> {{ $order->customer_name ?? '—' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <strong>{{ $appName }}</strong>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Customer (Account)</strong></p>
                                        <p class="mb-0">{{ $order->user?->name }}<br>{{ $order->user?->email }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Delivery</strong></p>
                                        <p class="mb-0">
                                            @if($order->shipping_address){{ $order->shipping_address }}<br>@endif
                                            {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
                                            @if($order->shipping_phone){{ $order->shipping_phone }}@endif
                                        </p>
                                    </div>
                                </div>

                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Unit price</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name }} ({{ $item->item_code }})</td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                            <td class="text-end"><strong>₦{{ number_format($order->subtotal, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>

                                <p class="text-muted small mb-0 mt-3">Thank you for your order.</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    @include('partials.pwa-scripts')
</body>
</html>
