<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Items for Supply</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 8px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Items for Supply</h1>
            <p class="meta">Date: {{ now()->format('M d, Y H:i') }}</p>
        </div>
        <div class="text-end">
            <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Product Name</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $row)
            <tr>
                <td>{{ $row['item_code'] }}</td>
                <td>{{ $row['product_name'] }}</td>
                <td class="text-end">{{ $row['quantity'] }}</td>
                <td class="text-end">₦{{ number_format($row['unit_price'] ?? 0, 0) }}</td>
                <td class="text-end">₦{{ number_format($row['line_total'] ?? 0, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong>₦{{ number_format($total, 0) }}</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
