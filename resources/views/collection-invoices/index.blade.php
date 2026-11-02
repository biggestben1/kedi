@extends('layouts.kedi-pos')

@section('content')
    @php
        $rows = $invoices->getCollection()->map(function ($invoice) {
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

            return [
                'number' => $invoice->invoice_number,
                'date' => $invoice->created_at->format('M d, Y'),
                'customer' => $invoice->customer_name,
                'phone' => $invoice->customer_phone,
                'kd' => $order?->kd_id,
                'group' => $order?->orderGroup?->displayName(),
                'center' => $order?->collectionBranch?->name,
                'total' => (float) $invoice->subtotal,
                'status' => $status,
                'statusLabel' => $statusLabel,
                'createdBy' => $invoice->user?->name,
                'url' => route('collection-invoices.show', $invoice),
            ];
        })->values();

        $payload = [
            'page' => 'list',
            'pos' => $pos,
            'rows' => $rows,
            'stats' => $stats,
            'centers' => $centers->map(fn ($center) => ['id' => $center->id, 'name' => $center->name])->values(),
            'filters' => $filters,
            'createUrl' => route('collection-invoices.create'),
            'indexUrl' => route('collection-invoices.index'),
            'pagination' => [
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
                'total' => $invoices->total(),
                'current' => $invoices->currentPage(),
                'last' => $invoices->lastPage(),
                'prev' => $invoices->previousPageUrl(),
                'next' => $invoices->nextPageUrl(),
            ],
        ];
    @endphp
    <script>window.__KEDI_INVOICE__ = @json($payload);</script>
    <div id="kedi-invoice-root"></div>
    <noscript><a href="{{ route('collection-invoices.create') }}">New Invoice</a></noscript>
@endsection
