<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Receipt – {{ $order->invoice_number ?? 'ORD-' . $order->id }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <style>
        :root {
            --slip-width: 72mm;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #e8e8e8;
            font-family: "Courier New", Courier, monospace;
            color: #111;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print-bar {
            max-width: var(--slip-width);
            margin: 12px auto;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .no-print-bar a, .no-print-bar button {
            font-family: system-ui, sans-serif;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #fff;
            color: #222;
            text-decoration: none;
            cursor: pointer;
        }
        .no-print-bar .btn-primary {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }
        .alert-success {
            max-width: var(--slip-width);
            margin: 0 auto 10px;
            padding: 8px 10px;
            background: #d1e7dd;
            border: 1px solid #a3cfbb;
            border-radius: 6px;
            font-family: system-ui, sans-serif;
            font-size: 13px;
        }
        .pos-slip {
            width: var(--slip-width);
            max-width: 100%;
            margin: 0 auto 24px;
            background: #fff;
            padding: 6px 4px 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.12);
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .pos-slip .center { text-align: center; }
        .pos-slip .brand {
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .pos-slip .muted {
            font-size: 10px;
            line-height: 1.3;
            overflow-wrap: anywhere;
            word-break: break-all;
        }
        .pos-slip .line {
            border: 0;
            border-top: 1px dashed #333;
            margin: 5px 0;
        }
        .pos-slip .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 4px;
            font-size: 10px;
            line-height: 1.3;
        }
        .pos-slip .row .left {
            flex: 1 1 auto;
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .pos-slip .row .amt {
            flex: 0 0 auto;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }
        .pos-slip .item {
            margin-top: 5px;
        }
        .pos-slip .item:first-of-type { margin-top: 0; }
        .pos-slip .item-name {
            font-size: 11px;
            line-height: 1.25;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .pos-slip .item-line {
            display: flex;
            justify-content: space-between;
            gap: 4px;
            font-size: 10px;
            line-height: 1.3;
            margin-top: 1px;
        }
        .pos-slip .item-line .detail {
            flex: 1 1 auto;
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .pos-slip .item-line .amt {
            flex: 0 0 auto;
            text-align: right;
            white-space: nowrap;
        }
        .pos-slip .total-row {
            font-size: 12px;
            font-weight: 700;
            margin-top: 3px;
        }
        .pos-slip .section-label {
            font-size: 10px;
            text-transform: uppercase;
            margin: 4px 0 2px;
            font-weight: 700;
        }
        .pos-slip .wrap {
            overflow-wrap: anywhere;
            word-break: break-all;
        }
        @media print {
            @page {
                margin: 0;
            }
            html, body {
                width: 100%;
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print { display: none !important; }
            .pos-slip {
                width: 100%;
                max-width: 72mm;
                margin: 0;
                box-shadow: none;
                padding: 1.5mm 1.5mm 3mm;
            }
        }
    </style>
</head>
<body>
@php
    $appName = config('app.name');
    $orders = ($placedOrdersFull ?? collect())->isNotEmpty()
        ? $placedOrdersFull
        : collect([$order]);
    $first = $orders->first();
    $group = $first?->orderGroup;
    $breakdown = $first?->payment_breakdown ?? [];
    $grandSubtotal = (float) $orders->sum('subtotal');
    $discount = (float) ($breakdown['discount'] ?? $orders->sum('discount_amount'));
    $grandTotal = isset($breakdown['total'])
        ? (float) $breakdown['total']
        : max(0, $grandSubtotal - $discount);

    $money = static function (float $amount): string {
        return 'N'.number_format($amount, 2, '.', ',');
    };

    $paymentParts = [];
    foreach (['wallet' => 'Wallet', 'kd_credit' => 'KD Credit', 'dpbv' => 'DPBV', 'cash' => 'Cash', 'cheque' => 'Cheque', 'pos' => 'POS', 'bank' => 'Bank'] as $key => $label) {
        $amt = (float) ($breakdown[$key] ?? 0);
        if ($amt > 0) {
            $paymentParts[] = $label.': '.$money($amt);
        }
    }
    if ($paymentParts === []) {
        $paymentParts[] = $first?->paymentLabel() ?? '—';
    }
@endphp

    <div class="no-print no-print-bar">
        <button type="button" class="btn-primary" onclick="window.print()">Print</button>
        <a href="{{ route('orders.index') }}">My Orders</a>
        @if($group)
            <a href="{{ route('order-groups.show', $group) }}">Order Group</a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert-success no-print">{{ session('success') }}</div>
    @endif

    <div class="pos-slip" id="pos-receipt">
        <div class="center">
            <div class="brand">{{ $appName }}</div>
            <div class="muted">Sales receipt</div>
        </div>
        <hr class="line">

        <div class="muted">
            @if($group)
                <div class="wrap"><strong>Group:</strong> {{ $group->displayName() }}</div>
            @endif
            <div><strong>Date:</strong> {{ optional($first?->created_at)->format('d/m/Y H:i') }}</div>
            @if($orders->count() === 1)
                <div class="wrap"><strong>Order:</strong> {{ $first->invoice_number ?? 'ORD-'.$first->id }}</div>
            @else
                <div><strong>Orders:</strong> {{ $orders->count() }}
                    ({{ $orders->map(fn ($o) => $o->invoice_number ?? 'ORD-'.$o->id)->implode(', ') }})
                </div>
            @endif
            <div class="wrap"><strong>KD:</strong> {{ $first->kd_id ?? '—' }}</div>
            <div class="wrap"><strong>Cust:</strong> {{ $first->customer_name ?? $first->user?->name ?? '—' }}</div>
            @if(!empty($breakdown['pos_machine']))
                <div class="wrap"><strong>POS:</strong> {{ $breakdown['pos_machine'] }}</div>
            @endif
            @if(!empty($breakdown['bank_account']))
                <div class="wrap"><strong>Bank:</strong> {{ $breakdown['bank_account'] }}</div>
            @endif
        </div>

        <hr class="line">

        @foreach($orders as $ord)
            @if($orders->count() > 1)
                <div class="section-label">{{ $ord->invoice_number ?? 'ORD-'.$ord->id }}</div>
            @endif
            @foreach($ord->items as $item)
                <div class="item">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="item-line">
                        <div class="detail">{{ $item->quantity }} x {{ $money((float) $item->unit_price) }} ({{ $item->item_code }})</div>
                        <div class="amt">{{ $money((float) $item->line_total) }}</div>
                    </div>
                </div>
            @endforeach
        @endforeach

        <hr class="line">
        <div class="row">
            <div class="left">Subtotal</div>
            <div class="amt">{{ $money($grandSubtotal) }}</div>
        </div>
        @if($discount > 0)
            <div class="row">
                <div class="left wrap">Disc{{ !empty($breakdown['coupon_code']) ? ' '.$breakdown['coupon_code'] : '' }}</div>
                <div class="amt">-{{ $money($discount) }}</div>
            </div>
        @endif
        <div class="row total-row">
            <div class="left">TOTAL</div>
            <div class="amt">{{ $money($grandTotal) }}</div>
        </div>

        <hr class="line">
        <div class="section-label">Payment</div>
        @foreach($paymentParts as $part)
            <div class="muted wrap">{{ $part }}</div>
        @endforeach

        <hr class="line">
        <div class="center muted">
            Thank you<br>
            {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    @include('partials.pwa-scripts')
    <script>
        (function () {
            var params = new URLSearchParams(window.location.search);
            if (params.get('autoprint') === '1') {
                window.addEventListener('load', function () {
                    setTimeout(function () { window.print(); }, 300);
                });
            }
        })();
    </script>
</body>
</html>
