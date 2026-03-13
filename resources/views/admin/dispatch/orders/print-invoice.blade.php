<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #ddd; padding-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .mt-3 { margin-top: 24px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" class="btn" style="padding: 8px 16px; cursor: pointer; background: #0d6efd; color: #fff; border: none; border-radius: 4px;">Print</button>
        <a href="{{ route('admin.dispatch.orders.show', $order) }}" style="margin-left: 8px;">Back to order</a>
    </div>

    <div class="header">
        <div>
            <h1 style="margin: 0;">Invoice</h1>
            <p style="margin: 4px 0 0 0;">Order #: <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong></p>
            <p style="margin: 2px 0 0 0;">Date: {{ $order->created_at->format('M d, Y') }}</p>
        </div>
        <div class="text-end">
            <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>

    <p><strong>Bill to</strong><br>
        @if($order->kd_id)<strong>KD No:</strong> {{ $order->kd_id }}<br>@endif
        <strong>Name:</strong> {{ $order->customer_name ?? $order->user?->name ?? '—' }}<br>
        {{ $order->user?->email }}<br>
        @if($order->shipping_phone){{ $order->shipping_phone }}@endif
    </p>

    <p><strong>Ship to</strong><br>
        @if($order->shipping_address){{ $order->shipping_address }}<br>@endif
        {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
        {{ $order->shipping_phone }}
    </p>

    <table class="mt-3">
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
                <td colspan="3" class="text-end">Subtotal</td>
                <td class="text-end">₦{{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->shipping_cost > 0)
            <tr>
                <td colspan="3" class="text-end">Shipping Cost</td>
                <td class="text-end">₦{{ number_format($order->shipping_cost, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong>₦{{ number_format($order->subtotal + $order->shipping_cost, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
