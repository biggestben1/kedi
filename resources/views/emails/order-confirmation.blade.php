<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f5f5f5; font-weight: 600; }
        .footer { margin-top: 2rem; font-size: 0.9rem; color: #666; }
        a { color: #6C5FFC; text-decoration: none; }
    </style>
</head>
<body>
    <p style="margin-bottom: 1.5rem;">
        <img src="{{ config('app.url') }}/images/logo.png" alt="Optimal Consult Pharmacy" style="max-width: 200px; height: auto; display: block;" />
    </p>
    <p>Dear Valued Customer,</p>

    <p>Thank you for your purchase from Optimal Consult Pharmacy.</p>

    <p>Your order has been successfully received and processed. Please find your purchase details below:</p>

    <p>
        <strong>Order Number:</strong> {{ $order->invoice_number ?? 'ORD-' . $order->id }}<br>
        <strong>Order Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}<br>
        <strong>Payment Method:</strong> {{ $order->payment_method === 'wallet' ? 'Wallet' : 'Pay on Delivery' }}
    </p>

    <h2>Purchased Items</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                <td>{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p>
        <strong>Subtotal:</strong> {{ number_format((float) $order->subtotal, 2) }}<br>
        <strong>Delivery Fee:</strong> {{ number_format(0, 2) }}<br>
        <strong>Total Amount Paid:</strong> {{ number_format((float) $order->subtotal, 2) }}
    </p>

    <h2>Delivery Address</h2>
    <p>
        {{ $order->shipping_address }}<br>
        {{ $order->shipping_city }}{!! $order->shipping_state ? ', ' . e($order->shipping_state) : '' !!}{!! $order->shipping_postal_code ? ' ' . e($order->shipping_postal_code) : '' !!}<br>
        Phone: {{ $order->shipping_phone }}
    </p>

    <p>If you have any questions regarding your order, prescriptions, or delivery, please contact our support team through our website:</p>
    <p><a href="http://optimalconsult.org/">http://optimalconsult.org/</a></p>

    <p>We appreciate your trust in Optimal Consult Pharmacy and look forward to serving you again.</p>

    <p class="footer">
        Kind regards,<br>
        <strong>Optimal Consult Pharmacy Team</strong><br>
        <a href="http://optimalconsult.org/">http://optimalconsult.org/</a>
    </p>
</body>
</html>
