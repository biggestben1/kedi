<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        a { color: #6C5FFC; text-decoration: none; }
        .footer { margin-top: 2rem; font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
    <p style="margin-bottom: 1.5rem;">
        <img src="{{ config('app.url') }}/images/logo.png" alt="Optimal Consult Pharmacy" style="max-width: 200px; height: auto; display: block;" />
    </p>
    <p>Dear Valued Customer,</p>

    @if($statusLabel === 'Packed')
    <p>Good news! Your order <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong> has been successfully packed and is now being prepared for dispatch.</p>

    <p><strong>Order Details:</strong></p>
    <p>
        Order Number: {{ $order->invoice_number ?? 'ORD-' . $order->id }}<br>
        Order Status: Packed<br>
        Order Date: {{ $order->created_at->format('F j, Y \a\t g:i A') }}
    </p>

    <p>Your items have been carefully packaged and will be shipped shortly. You will receive another notification once your order is dispatched.</p>
    @elseif($statusLabel === 'Shipped')
    <p>Your order <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong> has been shipped and is now on its way to your delivery address.</p>

    <p><strong>Order Details:</strong></p>
    <p>
        Order Number: {{ $order->invoice_number ?? 'ORD-' . $order->id }}<br>
        Order Status: Shipped<br>
        Shipping Date: {{ $order->shipped_at?->format('F j, Y \a\t g:i A') ?? $order->updated_at->format('F j, Y \a\t g:i A') }}<br>
        @if($order->delivery_courier)Courier Service: {{ $order->delivery_courier }}<br>@endif
        @if($order->tracking_number)Tracking Number: {{ $order->tracking_number }}@endif
    </p>

    <p>You can use the tracking number above to monitor your delivery status. Please ensure someone is available to receive the package.</p>
    @elseif($statusLabel === 'Delivered')
    <p>We are pleased to inform you that your order <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong> has been successfully delivered.</p>

    <p><strong>Order Details:</strong></p>
    <p>
        Order Number: {{ $order->invoice_number ?? 'ORD-' . $order->id }}<br>
        Order Status: Delivered<br>
        Delivery Date: {{ $order->delivered_at?->format('F j, Y \a\t g:i A') ?? $order->updated_at->format('F j, Y \a\t g:i A') }}
    </p>

    <p>We hope you are satisfied with your purchase. If you have any questions, concerns, or feedback regarding your order, please feel free to contact us through our website:</p>
    @else
    <p>Your order <strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong> has been updated.</p>

    <p><strong>Order Details:</strong></p>
    <p>
        Order Number: {{ $order->invoice_number ?? 'ORD-' . $order->id }}<br>
        Order Status: {{ $statusLabel }}<br>
        Order Date: {{ $order->created_at->format('F j, Y \a\t g:i A') }}
    </p>

    @if($order->tracking_number)
    <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
    @endif

    @if($order->delivery_courier)
    <p><strong>Delivery Courier:</strong> {{ $order->delivery_courier }}</p>
    @endif
    @endif

    @if($statusLabel === 'Shipped')
    <p>If you need any assistance or have questions about your delivery, please contact us via:</p>
    @elseif($statusLabel === 'Delivered')
    @else
    <p>If you have any questions or need assistance, please contact us via:</p>
    @endif
    <p><a href="https://optimalconsult.org/">https://optimalconsult.org/</a></p>

    <p>Thank you for choosing Optimal Consult Pharmacy.@if(!in_array($statusLabel, ['Shipped', 'Delivered'])) We appreciate your trust and support.@endif</p>
    @if($statusLabel === 'Delivered')
    <p>Your satisfaction is very important to us, and we look forward to serving you again.</p>
    @endif

    <p class="footer">
        Kind regards,<br>
        <strong>Optimal Consult Pharmacy Team</strong><br>
        <a href="https://optimalconsult.org/">https://optimalconsult.org/</a>
    </p>
</body>
</html>
