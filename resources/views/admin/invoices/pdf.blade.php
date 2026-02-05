<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #666; margin-bottom: 12px; }
        .customer { margin-bottom: 16px; }
        .totals { margin-top: 12px; }
        .totals table { width: auto; margin-left: auto; }
    </style>
</head>
<body>
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
            <p class="meta">Date: {{ $invoice->invoice_date->format('Y-m-d') }} @if($invoice->due_date) | Due: {{ $invoice->due_date->format('Y-m-d') }} @endif | Status: {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>

    <div class="customer">
        <strong>Bill To</strong><br>
        @if($invoice->customer_name){{ $invoice->customer_name }}<br>@endif
        @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
        @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
        @if($invoice->user?->kid)<strong>Kid:</strong> {{ $invoice->user->kid }}<br>@endif
        @if($invoice->customer_address){!! nl2br(e($invoice->customer_address)) !!}@endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="text-end">Qty</th>
                <th>Unit</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item_name }}{!! $item->description ? '<br><small>'.e($item->description).'</small>' : '' !!}</td>
                    <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $item->unit ?? '—' }}</td>
                    <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td><strong>Subtotal</strong></td><td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td></tr>
            @if((float) $invoice->tax > 0)
                <tr><td>Tax</td><td class="text-end">{{ number_format($invoice->tax, 2) }}</td></tr>
            @endif
            @if((float) $invoice->discount > 0)
                <tr><td>Discount</td><td class="text-end">-{{ number_format($invoice->discount, 2) }}</td></tr>
            @endif
            <tr><td><strong>Total</strong></td><td class="text-end"><strong>{{ number_format($invoice->total, 2) }}</strong></td></tr>
        </table>
    </div>

    @if($invoice->notes)
        <p style="margin-top: 16px;"><strong>Notes</strong><br>{!! nl2br(e($invoice->notes)) !!}</p>
    @endif
</body>
</html>
