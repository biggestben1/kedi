<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 12px; }
        .customer { margin-bottom: 16px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Order Receipt</h1>
            <p class="meta">Order #: {{ $order->invoice_number ?? 'ORD-' . $order->id }} | Date: {{ $order->created_at->format('M d, Y H:i') }} | Payment: {{ $order->payment_method === 'wallet' ? 'Wallet' : 'Pay on Delivery' }}</p>
            <p class="meta">KD NO: {{ $order->kd_id ?? '—' }} | Customer Name: {{ $order->customer_name ?? '—' }}</p>
        </div>
        <div class="text-end">
            <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>

    <div class="customer">
        <strong>Customer (Account)</strong><br>
        {{ $order->user?->name }}<br>
        {{ $order->user?->email }}<br>
        <br>
        <strong>Delivery</strong><br>
        @if($order->shipping_address){{ $order->shipping_address }}<br>@endif
        {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
        @if($order->shipping_phone){{ $order->shipping_phone }}@endif
    </div>

    <table>
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

    <p style="margin-top: 16px; color: #666;">Thank you for your order.</p>
</body>
</html>
