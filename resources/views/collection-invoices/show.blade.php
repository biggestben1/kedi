@extends('layouts.kedi-pos')

@section('content')
    @php
        $order = $invoice->order;
        $cancelled = ! $order || $order->trashed();
        if ($invoice->isCollected()) {
            $status = 'done';
            $statusLabel = 'Collected '.$order->collected_at->format('M d, Y');
        } elseif ($cancelled) {
            $status = 'cancel';
            $statusLabel = 'Cancelled';
        } elseif ($order->status === \App\Models\Order::STATUS_DRAFT && $order->order_group_id) {
            $status = 'group';
            $statusLabel = 'In group';
        } else {
            $status = 'wait';
            $statusLabel = 'Waiting for collection';
        }

        $payload = [
            'page' => 'show',
            'pos' => $pos,
            'invoice' => [
                'number' => $invoice->invoice_number,
                'status' => $status,
                'statusLabel' => $statusLabel,
                'createdAt' => $invoice->created_at->format('M d, Y H:i'),
                'createdBy' => $invoice->user?->name,
                'customer' => $invoice->customer_name,
                'phone' => $invoice->customer_phone,
                'kd' => $order?->kd_id,
                'group' => $order?->orderGroup?->displayName(),
                'groupUrl' => $order?->orderGroup ? route('order-groups.show', $order->orderGroup) : null,
                'center' => $order?->collectionBranch?->name,
                'centerPhone' => $order?->collectionBranch?->phone,
                'total' => (float) $invoice->subtotal,
                'notes' => $invoice->notes,
                'canDelete' => ! $invoice->isCollected() && ! $cancelled,
                'items' => $invoice->items->map(fn ($item) => [
                    'name' => $item->item_name,
                    'stocked' => (bool) $item->product_id,
                    'quantity' => $item->quantity,
                    'unitPrice' => (float) $item->unit_price,
                    'lineTotal' => (float) $item->line_total,
                ])->values(),
            ],
            'urls' => [
                'index' => route('collection-invoices.index'),
                'destroy' => route('collection-invoices.destroy', $invoice),
            ],
        ];
    @endphp
    <script>window.__KEDI_INVOICE__ = @json($payload);</script>
    <div id="kedi-invoice-root"></div>
@endsection
