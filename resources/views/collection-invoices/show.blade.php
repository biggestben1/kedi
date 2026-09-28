@extends($layout)

@section('title', $pageTitle)

@section('content')
    @php
        $order = $invoice->order;
        $cancelled = ! $order || $order->trashed();
    @endphp

    @if($layout === 'layouts.admin')
        <div class="page-header">
            <h1 class="page-title">{{ $pageTitle }}</h1>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0">{{ $invoice->invoice_number }}</h3>
                <small class="text-muted">Created {{ $invoice->created_at->format('M d, Y H:i') }} by {{ $invoice->user?->name }}</small>
            </div>
            <div>
                @if($invoice->isCollected())
                    <span class="badge bg-success fs-6">Collected {{ $order->collected_at->format('M d, Y H:i') }}</span>
                @elseif($cancelled)
                    <span class="badge bg-secondary fs-6">Cancelled</span>
                @else
                    <span class="badge bg-warning text-dark fs-6">Waiting for collection</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-semibold">{{ $invoice->customer_name }}</div>
                    @if($invoice->customer_phone)
                        <div>{{ $invoice->customer_phone }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Collection center</div>
                    <div class="fw-semibold">{{ $order?->collectionBranch?->name ?: '—' }}</div>
                    @if($order?->collectionBranch?->phone)
                        <div>{{ $order->collectionBranch->phone }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Collection order</div>
                    <div class="fw-semibold">{{ $order?->invoice_number ?: '—' }}</div>
                    <div class="small text-muted">Customer pays at the collection center.</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit price</th>
                            <th class="text-end">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>
                                    {{ $item->item_name }}
                                    @unless($item->product_id)
                                        <br><small class="text-warning">Not in the product list — no stock removed at collection.</small>
                                    @endunless
                                </td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">₦{{ number_format($invoice->subtotal, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($invoice->notes)
                <div class="mt-3">
                    <div class="text-muted small">Notes</div>
                    <div>{!! nl2br(e($invoice->notes)) !!}</div>
                </div>
            @endif
        </div>
        <div class="card-footer d-flex justify-content-between flex-wrap gap-2 d-print-none">
            <a href="{{ route('collection-invoices.index') }}" class="btn btn-outline-secondary">Back to invoices</a>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="fe fe-printer me-1"></i>Print</button>
                @if(! $invoice->isCollected() && ! $cancelled)
                    <form method="POST" action="{{ route('collection-invoices.destroy', $invoice) }}" onsubmit="return confirm('Delete this invoice and remove it from the collection center?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="fe fe-trash-2 me-1"></i>Delete</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
