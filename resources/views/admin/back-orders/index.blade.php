@extends('layouts.admin')

@section('title', 'Back Orders')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Back Orders</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Back Orders</li>
            </ol>
        </div>
    </div>

    <div class="alert alert-info mb-3">
        <strong>When do back orders appear?</strong> A back order is created only when an invoice is moved to an order (via <strong>Approve</strong> or <strong>Move to dispatch</strong>) and at least one line has <em>quantity ordered greater than available stock</em>. The shortfall is listed here until you fulfill it. If every line had enough stock at the time, no back orders are created and this list stays empty.
    </div>

    <div class="card bg-white">
        <div class="card-header bg-light">
            <h3 class="card-title mb-0 text-dark">Pending Back Orders</h3>
        </div>
        <div class="card-body p-0 bg-white">
            <div class="table-responsive">
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="text-dark">Invoice</th>
                            <th class="text-dark">Customer</th>
                            <th class="text-dark">Item</th>
                            <th class="text-end text-dark">Qty Pending</th>
                            <th class="text-end text-dark">Unit Price</th>
                            <th class="text-dark">Created</th>
                            <th class="text-end text-dark">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backOrders as $bo)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $bo->invoice_id) }}">{{ $bo->invoice?->invoice_number ?? '#' . $bo->invoice_id }}</a>
                                </td>
                                <td>
                                    {{ $bo->user?->name ?? '—' }}
                                    @if($bo->user?->email)
                                        <br><small class="text-muted">{{ $bo->user->email }}</small>
                                    @endif
                                </td>
                                <td>{{ $bo->item_name }}</td>
                                <td class="text-end">{{ number_format($bo->quantity_pending, 0) }}</td>
                                <td class="text-end">₦{{ number_format($bo->unit_price, 2) }}</td>
                                <td>{{ $bo->created_at->format('M d, Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.invoices.show', $bo->invoice_id) }}" class="btn btn-sm btn-outline-primary">View Invoice</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No pending back orders.<br>
                                    <small>This is normal if no invoice had a shortfall (ordered &gt; stock) when the order was created.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($backOrders->hasPages())
            <div class="card-footer">
                {{ $backOrders->links() }}
            </div>
        @endif
    </div>
    </div>
@endsection
