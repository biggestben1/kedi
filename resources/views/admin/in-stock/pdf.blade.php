<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factory Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 12px; }
        .brought { font-weight: bold; }
        .logo { max-width: 120px; }
    </style>
</head>
<body>
    <h1>Factory Invoice {{ $invoice->invoice_number }}</h1>
    <p class="meta">
        Date: {{ $invoice->invoice_date->format('Y-m-d') }}
        @if($invoice->factory_name) | Factory: {{ $invoice->factory_name }} @endif
    </p>

    @if($invoice->notes)
        <p><strong>Notes:</strong> {!! nl2br(e($invoice->notes)) !!}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:40px">Brought</th>
                <th>Product</th>
                <th class="text-end">Quantity</th>
                <th>Status</th>
                <th class="text-end">Cost Price</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr class="{{ $item->is_brought ? 'brought' : '' }}">
                    <td class="text-center">{{ $item->is_brought ? '✓' : '—' }}</td>
                    <td>{{ $item->product_name }} <small>({{ $item->item_code }})</small></td>
                    <td class="text-end">{{ number_format($item->quantity) }}</td>
                    <td>{{ $statusOptions[$item->status] ?? $item->status }}</td>
                    <td class="text-end">₦{{ number_format($item->cost_price, 2) }}</td>
                    <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 16px; font-size: 9px; color: #666;">
        Generated on {{ now()->format('Y-m-d H:i') }} | Brought = items received
    </p>
</body>
</html>
