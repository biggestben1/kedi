@extends($layout)

@section('title', $pageTitle)

@section('content')
    @if($layout === 'layouts.admin')
        <div class="page-header">
            <h1 class="page-title">{{ $pageTitle }}</h1>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0">Invoices sent to collection centers</h3>
                <small class="text-muted">No stock is checked when you create an invoice. Stock is removed from the collection center when the customer collects.</small>
            </div>
            <a href="{{ route('collection-invoices.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New Invoice</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Kedi number</th>
                            <th>Group</th>
                            <th>Collection center</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Created by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                                <td>
                                    {{ $invoice->customer_name }}
                                    @if($invoice->customer_phone)
                                        <br><small class="text-muted">{{ $invoice->customer_phone }}</small>
                                    @endif
                                </td>
                                <td>{{ $invoice->order?->kd_id ?: '—' }}</td>
                                <td>{{ $invoice->order?->orderGroup?->displayName() ?: '—' }}</td>
                                <td>{{ $invoice->order?->collectionBranch?->name ?: '—' }}</td>
                                <td class="text-end">₦{{ number_format($invoice->subtotal, 2) }}</td>
                                <td>
                                    @if($invoice->isCollected())
                                        <span class="badge bg-success">Collected {{ $invoice->order->collected_at->format('M d, Y') }}</span>
                                    @elseif($invoice->order?->trashed() || ! $invoice->order)
                                        <span class="badge bg-secondary">Cancelled</span>
                                    @elseif($invoice->order->status === \App\Models\Order::STATUS_DRAFT && $invoice->order->order_group_id)
                                        <span class="badge bg-info">In group</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Waiting for collection</span>
                                    @endif
                                </td>
                                <td>{{ $invoice->user?->name }}</td>
                                <td class="text-end">
                                    <a href="{{ route('collection-invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-muted p-4">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
