<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pharmacy Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        h1 { font-size: 14px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Pharmacy Sales Report</h1>
    <p class="meta">From {{ $from }} to {{ $to }}</p>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Selling Price</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Profit</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesLines as $row)
                <tr>
                    <td>{{ $row->invoice_number }}</td>
                    <td>{{ $row->order_date->format('Y-m-d H:i') }}</td>
                    <td>{{ $row->customer_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td class="text-end">{{ $row->quantity_sold }}</td>
                    <td class="text-end">{{ number_format($row->selling_price, 2) }}</td>
                    <td class="text-end">{{ $row->discount }}</td>
                    <td class="text-end">{{ number_format($row->profit, 2) }}</td>
                    <td>{{ $row->payment_status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No sales in date range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
