@php
    $breakdown = is_array($order->payment_breakdown) ? $order->payment_breakdown : [];
    $labels = [
        'wallet' => 'Wallet',
        'kd_credit' => 'KD Credit',
        'dpbv' => 'DPBV',
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'pos' => 'POS',
        'bank' => 'Bank',
    ];
@endphp
<div class="border rounded p-2 mb-2 bg-light small">
    <div><strong>Payment:</strong> {{ $order->paymentLabel() }}</div>
    @foreach($labels as $key => $label)
        @if((float) ($breakdown[$key] ?? 0) > 0)
            <div>{{ $label }}: ₦{{ number_format($breakdown[$key], 2) }}</div>
        @endif
    @endforeach
    @if(!empty($breakdown['pos_machine']))
        <div class="text-muted">POS: {{ $breakdown['pos_machine'] }}</div>
    @endif
    @if(!empty($breakdown['bank_account']))
        <div class="text-muted">Bank: {{ $breakdown['bank_account'] }}</div>
    @endif
</div>
