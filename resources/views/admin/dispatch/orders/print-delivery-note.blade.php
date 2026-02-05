<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 24px; border-bottom: 1px solid #ddd; padding-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .mt-3 { margin-top: 24px; }
        .sign-box { margin-top: 40px; border: 1px solid #ddd; padding: 12px; width: 200px; }
        @media print { body { padding: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; background: #0d6efd; color: #fff; border: none; border-radius: 4px;">Print</button>
        <a href="{{ route('admin.dispatch.orders.show', $order) }}" style="margin-left: 8px;">Back to order</a>
    </div>

    <div class="header">
        <div>
            <h1 style="margin: 0;">Delivery Note</h1>
            <p style="margin: 4px 0 0 0;">Order #: <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong></p>
            <p style="margin: 2px 0 0 0;">Date: {{ $order->created_at->format('M d, Y') }}</p>
        </div>
        <div class="text-end">
            <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>

    <p><strong>Deliver to</strong><br>
        {{ $order->user?->name }}<br>
        {{ $order->shipping_address }}<br>
        {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
        Phone: {{ $order->shipping_phone }}
    </p>

    <table class="mt-3">
        <thead>
            <tr>
                <th>Item / Code</th>
                <th class="text-end">Qty</th>
                <th>Checked</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }} ({{ $item->item_code }})</td>
                <td class="text-end">{{ $item->quantity }}</td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sign-box mt-3">
        <p style="margin: 0 0 8px 0;"><strong>Received by</strong></p>
        <p style="margin: 0; border-bottom: 1px solid #333; min-height: 20px;">&nbsp;</p>
        <p style="margin: 4px 0 0 0; font-size: 11px;">Signature / Date</p>
    </div>
</body>
</html>
