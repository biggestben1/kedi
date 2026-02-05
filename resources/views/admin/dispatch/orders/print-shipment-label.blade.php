<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Label – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; max-width: 400px; margin: 20px auto; padding: 24px; border: 2px solid #333; }
        .tracking { font-size: 18px; font-weight: bold; margin-bottom: 16px; letter-spacing: 1px; }
        .to { margin-top: 20px; line-height: 1.5; }
        @media print { body { margin: 0; border: none; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px;">
        <button onclick="window.print()" style="padding: 8px 16px; cursor: pointer; background: #0d6efd; color: #fff; border: none; border-radius: 4px;">Print label</button>
        <a href="{{ route('admin.dispatch.orders.show', $order) }}" style="margin-left: 8px;">Back to order</a>
    </div>

    <p style="margin: 0 0 8px 0; font-size: 12px;">{{ config('app.name') }}</p>
    <p class="tracking">{{ $order->tracking_number }}</p>

    <p class="to">
        <strong>TO:</strong><br>
        {{ $order->user?->name }}<br>
        {{ $order->shipping_address }}<br>
        {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_postal_code ?? '' }}<br>
        {{ $order->shipping_phone }}
    </p>

    @if($order->delivery_courier)
        <p style="margin-top: 16px; font-size: 12px; color: #666;">Courier: {{ $order->delivery_courier }}</p>
    @endif
</body>
</html>
